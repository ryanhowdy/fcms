<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Relationship;
use App\Models\User;
use App\FamilyTree;

class FamilyTreeController extends Controller
{
    /**
     * Show the family tree main page
     *
     * @return Illuminate\View\View
     */
    public function index()
    {
        $user = User::findOrFail(Auth()->user()->id);

        // Needs to be in the following format
        // [
        //   id => ...
        //   ...
        //   spouses => [...],
        //   kids    => [...],
        // ]
        $tree = [];

        $familyTree = new FamilyTree();

        // Get starting user id for this user, either this user or this user's parent
        $startingId = $familyTree->getStartingUserId($user->id);

        $relationships = Relationship::join('users as u', 'relationships.user_id', '=', 'u.id')
            ->join('users as ru', 'relationships.rel_user_id', '=', 'ru.id')
            ->select('relationships.*', 'u.fname as user_fname', 'u.mname as user_mname', 'u.lname as user_lname', 
                'u.maiden as user_maiden', 'u.dob_year as user_dob_year', 'u.dod_year as user_dod_year', 'u.avatar as user_avatar', 
                'ru.fname', 'ru.mname', 'ru.lname', 'ru.maiden', 'ru.dob_year', 'ru.dod_year', 'ru.avatar')
            ->get();

        $sorted = [];
        foreach ($relationships as $rel)
        {
            if (!isset($sorted[$rel->user_id]))
            {
                $sorted[$rel->user_id] = [
                    'id'          => $rel->user_id,
                    'user_id'     => $rel->user_id,
                    'rel_user_id' => $rel->rel_user_id,
                    'fname'       => $rel->user_fname,
                    'mname'       => $rel->user_mname,
                    'lname'       => $rel->user_lname,
                    'maiden'      => $rel->user_maiden,
                    'dob_year'    => $rel->user_dob_year,
                    'dod_year'    => $rel->user_dod_year,
                    'avatar'      => $rel->user_avatar,
                    'spouse'      => [],
                    'kids'        => [],
                ];
            }

            if ($rel->relationship == 'CHIL')
            {
                $arr = $rel->toArray();

                $arr['id'] = $rel->rel_user_id;

                $sorted[$rel->user_id]['kids'][] = $arr;
            }
            else if (in_array($rel->relationship, ['HUSB', 'WIFE']))
            {
                $arr = $rel->toArray();

                $arr['id'] = $rel->rel_user_id;

                $sorted[$rel->user_id]['spouses'][] = $arr;
            }
        }

        $branches = array_keys($sorted);

        if (isset($sorted[$startingId]))
        {
            $tree[$startingId] = $sorted[$startingId];

            foreach ($tree[$startingId]['kids'] as $i => $t)
            {
                // do these kids have branches of their own?
                if (in_array($t['rel_user_id'], $branches))
                {
                    // add spouses
                    foreach ($sorted[ $t['rel_user_id'] ]['spouses'] as $z)
                    {
                        $tree[$startingId]['kids'][$i]['spouses'][] = $z;
                    }
                    // add kids
                    foreach ($sorted[ $t['rel_user_id'] ]['kids'] as $z)
                    {
                        $tree[$startingId]['kids'][$i]['kids'][] = $z;
                    }
                }
            }
        }
        else
        {
            $tree[$startingId] = $user;
        }

        return view('tree.index', [
            'tree' => $tree,
        ]);
    }

    /**
     * Save the relationship to the db
     *
     * @return Illuminate\View\View
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'fname'        => ['required'],
            'lname'        => ['required'],
            'user_id'      => ['required', 'integer'],
            'relationship' => ['required'],
            'dob'          => ['sometimes', 'nullable', 'before_or_equal:today'],
            'dod'          => ['sometimes', 'nullable', 'before_or_equal:today'],
        ]);

        // Create the new user
        $newUser = new User;

        $newUser->fname = $request->input('fname');
        $newUser->lname = $request->input('lname');
        $newUser->email = $newUser->fname.'-'.$newUser->lname.'@email.com';

        // names
        if ($request->has('mname'))
        {
            $newUser->mname = $request->input('mname');
        }
        if ($request->has('maiden'))
        {
            $newUser->maiden = $request->input('maiden');
        }

        // dates
        if ($request->has('dob'))
        {
            $newUser->dob_year  = substr($request->input('dob'), 0, 4);
            $newUser->dob_month = substr($request->input('dob'), 5, 2);
            $newUser->dob_day   = substr($request->input('dob'), 8, 2);
        }
        if ($request->has('dod'))
        {
            $newUser->dod_year  = substr($request->input('dod'), 0, 4);
            $newUser->dod_month = substr($request->input('dod'), 5, 2);
            $newUser->dod_day   = substr($request->input('dod'), 8, 2);
        }

        $newUser->save();

        // Create the new relationship
        $relationship = new Relationship;

        $relationship->created_user_id = Auth()->user()->id;
        $relationship->updated_user_id = Auth()->user()->id;

        if ($request->input('relationship') == 'parent')
        {
            $relationship->user_id      = $newUser->id;
            $relationship->relationship = 'CHIL';
            $relationship->rel_user_id  = $request->input('user_id');
        }
        if ($request->input('relationship') == 'spouse')
        {
            $relationship->user_id      = $request->input('user_id');
            $relationship->relationship = 'WIFE';
            $relationship->rel_user_id  = $newUser->id;
        }
        if ($request->input('relationship') == 'child')
        {
            $relationship->user_id      = $request->input('user_id');
            $relationship->relationship = 'CHIL';
            $relationship->rel_user_id  = $newUser->id;
        }

        $relationship->save();

        return redirect()->route('familytree');
    }
}
