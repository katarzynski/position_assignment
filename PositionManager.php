<?php


namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\Episode;
use App\Models\Part;
use Exception;

class PositionManager
{

    /**
     * Creates new part record and places it on a list - reataching position
     * @param int $episode_id
     * @param int $dest_position
     * @return array|Exception
     */
    public function create($episode_id, $dest_position)
    {
        try {
            $new_part = DB::transaction(function () use ($episode_id, $dest_position) {
                Part::where('position', '>=', $dest_position)
                    ->update(['position' => DB::raw('position + 1')]);

                $part = new Part();
                $part->episode_id = $episode_id;
                $part->position = $dest_position;
                $part->save();

                return $part;
            });

            return $this->list($new_part->episode_id);
        } catch (Exception $e) {
            //Handle Exception in any way
            throw new Exception($e);
        }
    }

    /**
     * List parts for given episode, optional sorting returns Exception on fail.
     * @param int $episode
     * @param string $sort asc|desc
     * @return array|Exception
     */
    public function list($episode_id, $sort = 'asc')
    {

        try {
            $episode = Episode::with('parts')->findOrFail($episode_id);
            $parts = $episode->parts()
                ->orderBy('position', $sort)
                ->get()
                ->pluck('position', 'id')
                ->toArray();
            return $parts;
        } catch (Exception $e) {
            //Handle Exception in any way
            throw new Exception($e);
        }
    }

    /**
     * Move parts in episode. Main assumption is: position can have positive or negative values
     * but they have to keep required order.
     * @param int $part_id
     * @param int $dest_position
     * @return array|Exception
     */
    public function move($part_id, $dest_position)
    {
        try {
            // Check if part exists
            $part = Part::findOrFail($part_id);

            // Do nothing when current position is equal with destination
            if ($part->position == $dest_position) {
                return $this->list($part->episode->id);
            }

            DB::transaction(function () use ($part, $dest_position) {
                $episode_id = $part->episode->id;

                // First we need to update position
                $query_builder = Part::where('episode_id', $episode_id);

                if ($part->position > $dest_position) {
                    $query_builder->where('position', '>=', $dest_position)
                        ->where('position', '<', $part->position)
                        ->update(['position' => DB::raw('position + 1')]);
                } elseif ($part->position < $dest_position) {
                    $query_builder->where('position', '<=', $dest_position)
                        ->where('position', '>', $part->position)
                        ->update(['position' => DB::raw('position - 1')]);
                }

                // Set new part position
                $part->update(['position' => $dest_position]);
            });

            return $this->list($part->episode->id);
        } catch (Exception $e) {
            //Handle Exception in any way
            throw new Exception($e);
        }
    }

    /**
     * Delete one part from Episode
     * @param int $part_id
     * @return array|Exception
     */
    public function delete($part_id)
    {
        /**
         * Business logic doesn't say about require to recalculating positions
         * so they are kept in asc/desc order with assumption that they can have blank position numbers
         */
        try {
            // Check if part exists
            $part = Part::findOrFail($part_id);
            $episode = $part->episode->id;

            $part->delete();

            return $this->list($episode);
        } catch (Exception $e) {
            //Handle Exception in any way
            throw new Exception($e);
        }
    }
}
