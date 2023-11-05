<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Calendar;
use Carbon\Carbon;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\User;
use App\Models\Invitation;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\Invitation as MailInvitation;

class InvitationController extends Controller
{
    /**
     * create 
     * 
     * @param int $eventId 
     * @return Illuminate\Support\Facades\View
     */
    public function create(int $eventId)
    {
        $event = Event::findOrFail($eventId);

        $allUsers = User::where('id', '>', 1)
            ->where('id', '!=', Auth()->user()->id)
            ->orderBy('name', 'desc')
            ->get()
            ->pluck('name', 'id')
            ->toArray();

        return view('invitations.create', [
            'event' => $event->toArray(),
            'users' => $allUsers,
        ]);
    }

    /**
     * store 
     * 
     * @param int $eventId 
     * @param Request $request 
     * @return Illuminate\Support\Facades\View
     */
    public function store(int $eventId, Request $request)
    {
        $validated = $request->validate([
            'invite-all'  => ['required_without_all:members,non-members', 'nullable', 'int'],
            'members'     => ['required_without_all:invite-all,non-members', 'nullable', 'array'],
            'non-members' => ['required_without_all:members,invite-all', 'nullable'],
        ]);

        $event = Event::findOrFail($eventId);

        $existingInvitations = Invitation::where('event_id', $eventId)
            ->get()
            ->keyBy('user_id');

        $invitees = [];
        $members  = $request->members;

        // Add the current user to the invitation list
        $members[] = Auth()->user()->email;

        // non member emails
        if ($request->has('non-members'))
        {
            $nonMembers = explode("\n", $request->input('non-members'));

            $invitees = array_merge($invitees, $nonMembers);
        }

        // all members
        if ($request->has('invite-all'))
        {
            $users = User::where('activated', 1)
                ->get()
                ->toArray();

            $invitees = array_merge($invitees, $users);
        }
        // individual members
        else
        {
            $users = User::whereIn('id', $members)
                ->get()
                ->toArray();

            $invitees = array_merge($invitees, $users);
        }

        foreach ($invitees as $invitee)
        {
            $code = Str::random(13);

            // create new invitations
            $invitation = new Invitation;

            $user     = 0;
            $toEmail  = '';
            $toName   = '';
            $fromName = getUserDisplayName(Auth()->user()->toArray());
            $url      = '';

            // member
            if (is_array($invitee))
            {
                $toEmail = $invitee['email'];
                $toName  = getUserDisplayName($invitee);
                $url     = route('calendar.show', $eventId);

                $invitation->user_id = $invitee['id'];
            }
            // non member
            else
            {
                $toEmail = $invitee;
                $toName  = $invitee;
                $url     = route('invitations.show', [$eventId, $code]);

                $invitation->email = $toEmail;
                $invitation->code  = $code;
            }

            // Skip email address that have already been invited
            if (isset($existingInvitations[$toEmail]))
            {
                continue;
            }

            $invitation->event_id = $eventId;

            $invitation->save();

            // Send email
            Mail::to($toEmail)->send(new MailInvitation($event->title, $toName, $fromName, $url));
        }

        return redirect()->route('calendar.show', $eventId);
    }

    /**
     * show
     * 
     * @param int $eventId 
     * @param string $code 
     * @return Illuminate\Support\Facades\View
     */
    public function show(int $eventId, string $code)
    {
        $event = Event::findOrFail($eventId)->toArray();

        $cDatetime = Carbon::parse($event['date'] . ' ' . $event['time_start'], Auth()->user()->settings->timezone);

        $event['dateFormatted']      = $cDatetime->format('l, F j, Y');
        $event['timeStartFormatted'] = $cDatetime->format('g:ia');

        $invitations = Invitation::where('event_id', $eventId)
            ->leftJoin('users as u', 'invitations.user_id', '=', 'u.id')
            ->select('invitations.*', 'u.name', 'u.displayname')
            ->get();

        $counts = [
            'attending' => 0,
            'maybe'     => 0,
            'no'        => 0,
            'none'      => 0,
        ];
        $groupInvitations = [
            'all'       => [],
            'attending' => [],
            'maybe'     => [],
            'no'        => [],
            'none'      => [],
        ];

        foreach($invitations as $invite)
        {
            if (!is_null($invite->attending))
            {
                if ($rsvp->attending)
                {
                    $counts['attending']++;

                    $invite->status = 'Attending';

                    $groupInvitations['attending'][] = $invite->toArray();
                }
                else
                {
                    $counts['no']++;

                    $invite->status = 'No';

                    $groupInvitations['no'][] = $invite->toArray();
                }
            }
            elseif(!is_null($invite->response))
            {
                $counts['maybe']++;

                $invite->status = 'Maybe';

                $groupInvitations['maybe'][] = $invite->toArray();
            }
            else
            {
                $counts['none']++;

                $invite->status = 'None';

                $groupInvitations['none'][] = $invite->toArray();
            }

            $groupInvitations['all'][] = $invite->toArray();
        }

        // Validate the code is valid
        $rsvp = Invitation::where('code', $code)
            ->firstOrFail();

        $rsvp->rsvp = 'none';

        if (!is_null($rsvp->attending))
        {
            $rsvp->rsvp = $rsvp->attending ? 'attending' : 'no';
        }
        elseif(!is_null($rsvp->response))
        {
            $rsvp->rsvp = 'maybe';
        }

        return view('invitations.show', [
            'event'       => $event,
            'invitations' => $groupInvitations,
            'counts'      => $counts,
            'rsvp'        => $rsvp->toArray(),
        ]);
    }

    /**
     * update 
     * 
     * @param int $id 
     * @param Request $request 
     * @return Illuminate\Support\Facades\View
     */
    public function update(int $eid, int $id, Request $request)
    {
        $validated = $request->validate([
            'rsvp'     => ['required', 'string'],
            'comments' => ['sometimes', 'nullable', 'string'],
        ]);

        $invitation = Invitation::findOrFail($id);

        switch($request->rsvp)
        {
            case 'attending':
                $invitation->attending = 1;
                break;

            case 'maybe':
                $invitation->attending = null;
                break;

            case 'no':
                $invitation->attending = 1;
                break;
        }

        if ($request->has('comments'))
        {
            $invitation->response = $request->comments;
        }

        $invitation->save();

        return redirect()->back();
    }
}
