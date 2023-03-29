<?php

namespace App;

use Illuminate\Http\Request;
use App\Models\TreeIndividual;
use App\Models\TreeRelationship;
use App\Models\TreeFamily;

class FamilyTree
{
    protected $relationshipSortOrder = ['HUSB', 'WIFE', 'CHIL'];

    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct()
    {
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
     * getFamilyUnit 
     * 
     * Will return the individuals for the given family, formatted for tree display:
     * [
     *   id => ...
     *   ...
     *   spouses => [...],
     *   kids    => [...],
     * ]
     *
     * @param int $familyId 
     * @return array
     */
    public function getFamilyUnit (int $familyId)
    {
        $tree = [];

        // Get all individuals and their relationships for this family
        $individuals = TreeRelationship::from('tree_relationships as r')
            ->select('i.id', 'given_name', 'surname', 'maiden', 'dob_year', 'dob_month', 'dob_day', 'r.family_id', 'r.relationship')
            ->where('r.family_id', $familyId)
            ->join('tree_individuals as i', 'r.individual_id', '=', 'i.id')
            ->orderBy('dob_year')
            ->orderBy('dob_month')
            ->orderBy('dob_day')
            ->get();

        // sort the relationships
        $individuals = $individuals->sortBy(function($item) {
            return array_search($item['relationship'], $this->relationshipSortOrder);
        });

        $headOfHouseholdId = 0;

        // group and order the individuals/relationships
        foreach ($individuals as $ind)
        {
            //if (!isset($tree[$ind->family_id]))
            if (empty($tree))
            {
                $headOfHouseholdId                   = $ind->id;
                $tree[$headOfHouseholdId]            = $ind->toArray();
                $tree[$headOfHouseholdId]['spouses'] = [];
                $tree[$headOfHouseholdId]['kids']    = [];
            }
            else if ($ind->relationship == 'CHIL')
            {
                $tree[$headOfHouseholdId]['kids'][$ind->id] = $ind->toArray();
            }
            else if (in_array($ind->relationship, ['HUSB', 'WIFE']))
            {
                $tree[$headOfHouseholdId]['spouses'][$ind->id] = $ind->toArray();
            }
        }

        // now add the action strings
        $tree = $this->addTranslatedStrings($tree);

        return $tree;
    }

    /**
     * addTranslatedStrings 
     * 
     * @param string  $tree 
     * @return null
     */
    public function addTranslatedStrings ($tree)
    {
        foreach ($tree as $key => $ind)
        {
            $fullName = $ind['given_name'].' '.$ind['surname'];
            $parent1  = $fullName;
            $parent2  = __('unknown parent');

            $spouseKey = isset($ind['spouses']) ? array_key_first($ind['spouses']) : 0;

            if (isset($ind['spouses']) && count($ind['spouses']))
            {

                $parent2 = $ind['spouses'][$spouseKey]['given_name'].' '.$ind['spouses'][$spouseKey]['surname'];
            }

            $tree[$key]['strings'] = [
                'parent'  => [ 'header' => __('Add a parent for :name', [ 'name' => $fullName ]) ],
                'spouse'  => [ 'header' => __('Add a spouse for :name', [ 'name' => $fullName ]) ],
                'sibling' => [ 'header' => __('Add a sibling for :name', [ 'name' => $fullName ]) ],
                'child'   => [ 'header' => __('Add a child for :parent1 and :parent2', [ 'parent1' => $parent1, 'parent2' => $parent2 ]) ],
            ];
            if (isset($ind['spouses']) && count($ind['spouses']))
            {
                $tree[$key]['spouses'][$spouseKey]['strings'] = [
                    'parent'  => [ 'header' => __('Add a parent for :name', [ 'name' => $parent2 ]) ],
                    'spouse'  => [ 'header' => __('Add a spouse for :name', [ 'name' => $parent2 ]) ],
                    'sibling' => [ 'header' => __('Add a sibling for :name', [ 'name' => $parent2 ]) ],
                    'child'   => [ 'header' => __('Add a child for :parent1 and :parent2', [ 'parent1' => $parent1, 'parent2' => $parent2 ]) ],
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
