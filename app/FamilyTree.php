<?php

namespace App;

use App\Models\Relationship;

class FamilyTree
{
    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * getStartingUserId
     *
     * We need the id of the user we start the tree from.  Normally this
     * is the parent of the current user, but if no parents are configured
     * we just use the current user.
     *
     * @param int $userId
     * @return int
     */
    public function getStartingUserId (int $userId)
    {
        $oldestId = $userId;

        // get parents of given user
        $parents = Relationship::where('rel_user_id', $userId)
            ->where('relationship', 'CHIL')
            ->join('users as u', 'relationships.user_id', '=', 'u.id')
            ->select('relationships.*', 'u.fname', 'u.mname', 'u.lname', 'u.maiden', 'u.dob_year', 'u.dod_year', 'u.avatar')
            ->get();

        if ($parents->count())
        {
            $oldestId = $parents[0]->user_id;
        }

        return $oldestId;
    }
}
