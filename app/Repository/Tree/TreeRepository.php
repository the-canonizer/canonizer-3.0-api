<?php

namespace App\Repository\Tree;

use App\Models\Tree;
use App\Repository\Tree\TreeInterface;
use Illuminate\Support\Facades\Log;

class TreeRepository implements TreeInterface
{

    protected $model;
    /**
     * Instantiate a new TreeRepository instance.
     *
     * @return void
     */
    public function __construct(Tree $tree)
    {
        $this->model = $tree;
    }

    /**
     * create a new tree.
     *
     * @param  array tree
     * @return boolean Response
     */

    public function createTree($tree)
    {

        try {
            $record = Tree::create($tree);
            return $record->wasRecentlyCreated;
        } catch (\Throwable $th) {
            return false;
        }
    }

    /**
     * create or update a tree.
     *
     * @param  array tree
     * @param  array $conditions | assocative array
     *
     * @return boolean Response
     */

    public function upsertTree($treeArr, $conditions)
    {
        Log::info('TreeRepository::upsertTree called');
        Log::info('Conditions: ' . json_encode($conditions));
        try {
            $record = Tree::updateOrCreate(
                $conditions,
                $treeArr
            );
            Log::info('Tree upserted successfully for topic ' . $conditions['topic_id']);
            return $record;
        } catch (\Throwable $th) {
            \Log::error('Tree upsert failed: ' . $th->getMessage());
            return $th->getMessage();
        }
    }

    /**
     * find a tree.
     *
     * @param  array $conditions | assocative array
     *
     * @return array Response
     */
    // Find latest topic from MongoDB. 
    public function findLatestTree($conditions)
    {
        try {

            $start = microtime(true);

            // unset as_of_date condition because we have to find latest topic. 
            unset($conditions['as_of_date']);

            $record = Tree::where($conditions)->orderBy('as_of_date', 'desc')->limit(1)->get();

            return $record;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * find a tree.
     *
     * @param  array $conditions | assocative array
     *
     * @return array Response
     */

    public function findTree($conditions)
    {
        try {

            $record = Tree::where($conditions)->get();
            return $record;

        } catch (\Throwable $th) {
            throw $th;
        }
    }

}
