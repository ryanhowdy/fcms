<?php

namespace App;

use Illuminate\Http\Request;
use App\Models\TreeIndividual;
use App\Models\TreeRelationship;
use App\Models\TreeFamily;
use App\Models\User;

class FamilyTree
{
    protected $currentUser;
    protected $currentIndividual;
    protected $individuals;
    protected $trees;
    protected $tree;
    protected $oldestLkup;

    protected $relationshipSortOrder = ['HUSB', 'WIFE', 'CHIL'];

    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct(int $individualId = 0)
    {
        if (!empty($individualId))
        {
            $this->currentIndividual = TreeIndividual::where('id', $individualId)
                 ->first();
        }
    }

    /**
     * setUserId 
     * 
     * @param int $userId 
     * @return null
     */
    public function setUserId (int $userId)
    {
        $this->currentUser = User::findOrFail($userId);

        $this->currentIndividual = TreeIndividual::where('user_id', $this->currentUser->id)
             ->first();
    }

    /**
     * getFamilyTree 
     *
     * Will return the family tree array for the given user.
     * 
     * @return array
     */
    public function getFamilyTree ()
    {
        $this->getAllIndividuals();
        $this->groupIndividualsByFamily();
        $this->combineFamilies();
        $this->buildLkup($this->trees);

        $oldestId = $this->oldestLkup[$this->currentIndividual->id];

        $this->tree = [ $oldestId => $this->trees[$oldestId] ];

        $this->tree = $this->addTranslatedStrings($this->tree);

        return $this->tree;
    }

    /**
     * getAllIndividuals 
     * 
     * Gets all the individuals and their relationships from the database;
     *
     * @return null
     */
    private function getAllIndividuals ()
    {
        $this->individuals = TreeRelationship::from('tree_relationships as r')
            ->select('i.id', 'i.user_id', 'given_name', 'surname', 'maiden', 'sex', 'avatar', 'dob_year', 'dob_month', 'dob_day', 'r.family_id', 'r.relationship')
            ->join('tree_individuals as i', 'r.individual_id', '=', 'i.id')
            ->leftJoin('users as u', 'i.user_id', '=', 'u.id')
            ->orderBy('r.family_id')
            ->orderByRaw('case when relationship = "HUSB" then 1 when relationship = "WIFE" then 2 else 4 end')
            ->orderBy('dob_year')
            ->orderBy('dob_month')
            ->orderBy('dob_day')
            ->get();
    }

    /**
     * groupIndividualsByFamily 
     * 
     * Takes all the individuals from the db and groups them in families.
     *
     * Fills the $this->trees array that is keyed by the head of household's individual id.
     *
     * @return null
     */
    private function groupIndividualsByFamily ()
    {
        $lastFamilyId = 0;

        foreach ($this->individuals as $ind)
        {
            if ($lastFamilyId !== $ind->family_id)
            {
                if (!isset($this->trees[ $ind->id ]))
                {
                    $headOfHouseholdId = $ind->id;
                }
            }

            if (!isset($this->trees[$headOfHouseholdId]))
            {
                $this->trees[$headOfHouseholdId]            = $ind->toArray();
                $this->trees[$headOfHouseholdId]['spouses'] = [];
                $this->trees[$headOfHouseholdId]['kids']    = [];
            }
            else if (in_array($ind->relationship, ['HUSB', 'WIFE']))
            {
                $this->trees[$headOfHouseholdId]['spouses'][$ind->id] = $ind->toArray();
            }
            else if ($ind->relationship == 'CHIL')
            {
                $this->trees[$headOfHouseholdId]['kids'][$ind->id] = $ind->toArray();
            }

            $lastFamilyId = $ind->family_id;
        }
    }

    /**
     * combineFamilies 
     * 
     * Alters the $this->trees array by combining related families.
     * 
     * @return null
     */
    private function combineFamilies ()
    {
        foreach ($this->trees as $thisId => $thisFamily)
        {
            // do any of these spouses have their own family?
            foreach ($thisFamily['spouses'] as $spouseId => $spouseFamily)
            {
                foreach ($this->trees as $otherId => $otherFamily)
                {
                    if ($thisId === $otherId)
                    {
                        continue;
                    }

                    if ($spouseId === $otherId)
                    {
                        $this->trees[$thisId]['spouses'][$spouseId] = $this->trees[$spouseId];
                        unset($this->trees[$spouseId]);
                        break;
                    }
                }
            }

            // do any of these kids have their own family?
            foreach ($thisFamily['kids'] as $kidId => $kidFamily)
            {
                foreach ($this->trees as $otherId => $otherFamily)
                {
                    if ($thisId === $otherId)
                    {
                        continue;
                    }

                    if ($kidId === $otherId)
                    {
                        $this->trees[$thisId]['kids'][$kidId] = $this->trees[$kidId];
                        unset($this->trees[$kidId]);
                        break;
                    }
                }
            }
        }
    }

    /**
     * buildLkup 
     *
     * Will populate $this->oldestLkup with the oldest relative of each individual.
     * 
     * @param array $trees 
     * @param int   $oldestId 
     * @return null
     */
    private function buildLkup (array $trees, int $oldestId = null)
    {
        foreach ($trees as $individualId => $individual)
        {
            if ($oldestId != null)
            {
                $id = $oldestId;
            }
            else
            {
                $id = $individualId;
            }

            $this->oldestLkup[$individualId] = $id;

            if (isset($individual['spouses']))
            {
                foreach ($individual['spouses'] as $spouseId => $spouse)
                {
                    $this->oldestLkup[$spouseId] = $id;
                }
            }

            if (isset($individual['kids']))
            {
                $this->buildLkup($individual['kids'], $id);
            }
        }
    }

    /**
     * doesCurrentUserHaveFamilyTree 
     * 
     * @return boolean
     */
    public function doesCurrentUserHaveFamilyTree ()
    {
        if (!$this->currentIndividual)
        {
            return false;
        }

        return true;
    }

    /**
     * displayEmptyTree 
     * 
     * @return Illuminate\View\View
     */
    public function getEmptyTree ()
    {
        $userData = $this->currentUser->toArray();

        $names = explode(' ', $userData['name']);

        $userData['given_name'] = $names[0];
        $userData['surname']    = end($names);
        $userData['dob']        = $this->currentUser->birthday->format('Y-m-d');

        return view('tree.empty', [
            'user' => $userData,
        ]);
    }

    /**
     * addTranslatedStrings 
     * 
     * @param array $tree 
     * @return array
     */
    public function addTranslatedStrings ($tree)
    {
        foreach ($tree as $key => $ind)
        {
            $fullName = $ind['given_name'].' '.$ind['surname'];
            $parent1  = $fullName;
            $parent2  = gettext('unknown parent');

            $spouseKey = isset($ind['spouses']) ? array_key_first($ind['spouses']) : 0;

            if (isset($ind['spouses']) && count($ind['spouses']))
            {
                $parent2 = $ind['spouses'][$spouseKey]['given_name'].' '.$ind['spouses'][$spouseKey]['surname'];
            }

            $tree[$key]['strings'] = [
                'parent'  => [ 'header' => sprintf(gettext('Add a parent for %s'), $fullName) ],
                'spouse'  => [ 'header' => sprintf(gettext('Add a spouse for %s'), $fullName) ],
                'sibling' => [ 'header' => sprintf(gettext('Add a sibling for %s'), $fullName) ],
                'child'   => [ 'header' => sprintf(gettext('Add a child for %s and %s'), $parent1, $parent2) ],
            ];
            if (isset($ind['spouses']) && count($ind['spouses']))
            {
                $tree[$key]['spouses'][$spouseKey]['strings'] = [
                    'parent'  => [ 'header' => sprintf(gettext('Add a parent for %s'), $parent2) ],
                    'spouse'  => [ 'header' => sprintf(gettext('Add a spouse for %s'), $parent2) ],
                    'sibling' => [ 'header' => sprintf(gettext('Add a sibling for %s'), $parent2) ],
                    'child'   => [ 'header' => sprintf(gettext('Add a child for %s and %s'), $parent1, $parent2) ],
                ];
            }

            if (isset($ind['kids']))
            {
                $tree[$key]['kids'] = $this->addTranslatedStrings($ind['kids']);
            }
        }

        return $tree;
    }

    /**
     * getParentsFamilyId
     *
     * Will return the family id of the given individual's parents.
     *
     * @param int $individualId
     * @return int|bool
     */
    public function getParentsFamilyId (int $individualId)
    {
        $parents = TreeRelationship::where('individual_id', $individualId)
            ->where('relationship', 'CHIL')
            ->get();

        if ($parents->count())
        {
            return $parents[0]->family_id;
        }

        return false;
    }

    /**
     * addNewParent 
     * 
     * Creates a new famiy for the parent (unless the other parent already exists).
     * Added a CHIL relationship for the current individual, and a HUSB/WIFE
     * relationship for the new parent.
     *
     * @param TreeIndividual $individual 
     * @param Request        $request
     *
     * @return null
     */
    public function addNewParent (TreeIndividual $individual, Request $request)
    {
        // Does the current individual have a parent already
        $parentsFamilyId = $this->getParentsFamilyId($request->individual_id);

        // No
        if ($parentsFamilyId === false)
        {
            // Create a new Family
            $family = new TreeFamily();

            $family->created_user_id = Auth()->user()->id;
            $family->updated_user_id = Auth()->user()->id;

            $family->save();

            $parentsFamilyId = $family->id;
        }

        // Set the current individual as a new CHIL of this family
        $relationship = new TreeRelationship;

        $relationship->created_user_id = Auth()->user()->id;
        $relationship->updated_user_id = Auth()->user()->id;
        $relationship->individual_id   = $request->individual_id;
        $relationship->family_id       = $parentsFamilyId;
        $relationship->relationship    = 'CHIL';

        $relationship->save();

        // Set the new individual (parent) as a HUSB/WIFE of this family
        $rel = new TreeRelationship;

        $rel->individual_id   = $individual->id;
        $rel->family_id       = $parentsFamilyId;
        $rel->relationship    = $individual->sex == 'F' ? 'WIFE' : 'HUSB';
        $rel->created_user_id = Auth()->user()->id;
        $rel->updated_user_id = Auth()->user()->id;

        $rel->save();
    }

    /**
     * addNewSibling 
     *
     * @param TreeIndividual $individual 
     * @param Request        $request 
     *
     * @return null
     */
    public function addNewSibling (TreeIndividual $individual, Request $request)
    {
        dump($individual);
        dump($request->all());
        dd('addNewSibling - nope');
    }
}
