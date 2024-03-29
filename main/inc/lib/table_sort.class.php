<?php
/* For licensing terms, see /license.txt */
/**
 *	This is a library with some functions to sort tabular data
 *
 *	@package chamilo.library
 */
/**
 * Code
 */
define('SORT_DATE', 3);
define('SORT_IMAGE', 4);

/**
 *	@package chamilo.library
 */
class TableSort {

	/**
	 * Sorts 2-dimensional table.
	 * @param array $data The data to be sorted.
	 * @param int $column The column on which the data should be sorted (default = 0)
	 * @param string $direction The direction to sort (SORT_ASC (default) or SORT_DESC)
	 * @param constant $type How should data be sorted (SORT_REGULAR, SORT_NUMERIC,
	 * SORT_STRING,SORT_DATE,SORT_IMAGE)
	 * @return array The sorted dataset
	 * @author bart.mollet@hogent.be
	 */
	public function sort_table($data, $column = 0, $direction = SORT_ASC, $type = SORT_REGULAR) {
		if (!is_array($data) || empty($data)) {
			return array();
		}
		if ($column != strval(intval($column))) {
			// Probably an attack
			return $data;
		}
		if (!in_array($direction, array(SORT_ASC, SORT_DESC))) {
			// Probably an attack
			return $data;
		}

		if ($type == SORT_REGULAR) {
			if (TableSort::is_image_column($data, $column)) {
				$type = SORT_IMAGE;
			} elseif (TableSort::is_date_column($data, $column)) {
				$type = SORT_DATE;
			} elseif (TableSort::is_numeric_column($data, $column)) {
				$type = SORT_NUMERIC;
			} else {
				$type = SORT_STRING;
			}
		}

		$compare_operator = $direction == SORT_ASC ? '>' : '<=';
		switch ($type) {
			case SORT_NUMERIC:
				$compare_function = 'return strip_tags($a['.$column.']) '.$compare_operator.' strip_tags($b['.$column.']);';
				break;
			case SORT_IMAGE:
				$compare_function = 'return api_strnatcmp(api_strtolower(strip_tags($a['.$column.'], "<img>")), api_strtolower(strip_tags($b['.$column.'], "<img>"))) '.$compare_operator.' 0;';
				break;
			case SORT_DATE:
				$compare_function = 'return strtotime(strip_tags($a['.$column.'])) '.$compare_operator.' strtotime(strip_tags($b['.$column.']));';
				break;
			case SORT_STRING:
			default:
				$compare_function = 'return api_strnatcmp(api_strtolower(strip_tags($a['.$column.'])), api_strtolower(strip_tags($b['.$column.']))) '.$compare_operator.' 0;';
				break;
		}

		// Sort the content
		usort($data, create_function('$a, $b', $compare_function));

		return $data;
	}

	/**
	 * Sorts 2-dimensional table. It is possile changing the columns that will be shown and the way that the columns are to be sorted.
	 * @param array $data The data to be sorted.
	 * @param int $column The column on which the data should be sorted (default = 0)
	 * @param string $direction The direction to sort (SORT_ASC (default) orSORT_DESC)
	 * @param array $column_show The columns that we will show in the table i.e: $column_show = array('1','0','1') we will show the 1st and the 3th column.
	 * @param array $column_order Changes how the columns will be sorted ie. $column_order = array('0','3','2','3') The column [1] will be sorted like the column [3]
	 * @param constant $type How should data be sorted (SORT_REGULAR, SORT_NUMERIC, SORT_STRING, SORT_DATE, SORT_IMAGE)
	 * @return array The sorted dataset
	 * @author bart.mollet@hogent.be
	 */
	public function sort_table_config($data, $column = 0, $direction = SORT_ASC, $column_show = null, $column_order = null, $type = SORT_REGULAR, $doc_filter = false) {

		if (!is_array($data) || empty($data)) {
			return array();
		}
		if ($column != strval(intval($column))) {
			// Probably an attack
			return $data;
		}
		if (!in_array($direction, array(SORT_ASC, SORT_DESC))) {
			// Probably an attack
			return $data;
		}

		// Change columns sort
	 	// Here we say that the real way of how the columns are going to be order is manage by the $column_order array
	 	if (is_array($column_order)) {
	 		$column = isset($column_order[$column]) ? $column_order[$column] : $column;
	 	}

		if ($type == SORT_REGULAR) {
			if (TableSort::is_image_column($data, $column)) {
				$type = SORT_IMAGE;
			} elseif (TableSort::is_date_column($data, $column)) {
				$type = SORT_DATE;
			} elseif (TableSort::is_numeric_column($data, $column)) {
				$type = SORT_NUMERIC;
			} else {
				$type = SORT_STRING;
			}
		}
		
		//This fixs only works in the document tool when ordering by name
		if ($doc_filter && in_array($type, array(SORT_STRING))) {
    		$data_to_sort = $folder_to_sort = array();
    		$new_data = array();
    		if (!empty($data)) {
        		foreach ($data as $document) {
        		    if ($document['type'] == 'folder') {
        		      $docs_to_sort[$document['id']]   = api_strtolower($document['name']);
        		    } else {
        		      $folder_to_sort[$document['id']] = api_strtolower($document['name']);  
        		    }
        		    $new_data[$document['id']] = $document;    		        		     
        		}   
                if ($direction == SORT_ASC) {
                    if (!empty($docs_to_sort)) {
                        api_natrsort($docs_to_sort);
                    }
                    if (!empty($folder_to_sort)) {
                        api_natrsort($folder_to_sort);
                    }                
                } else {
                    if (!empty($docs_to_sort)) {
                        api_natsort($docs_to_sort);
                    }
                    if (!empty($folder_to_sort)) {
                        api_natsort($folder_to_sort);
                    }
                }
                $new_data_order = array();
                if (!empty($docs_to_sort)) {
                    foreach($docs_to_sort as $id => $document) {
                        $new_data_order[] = $new_data[$id];
                    }
                }
                if (!empty($folder_to_sort)) {
                    foreach($folder_to_sort as $id => $document) {
                        $new_data_order[] = $new_data[$id];
                    }
                }
                $data = $new_data_order; 		
    		}              
		} else {		
    		$compare_operator = $direction == SORT_ASC ? '>' : '<=';    		
    		switch ($type) {
    			case SORT_NUMERIC:
    				$compare_function = 'return strip_tags($a['.$column.']) '.$compare_operator.' strip_tags($b['.$column.']);';
    				break;
    			case SORT_IMAGE:
    				$compare_function = 'return api_strnatcmp(api_strtolower(strip_tags($a['.$column.'], "<img>")), api_strtolower(strip_tags($b['.$column.'], "<img>"))) '.$compare_operator.' 0;';
    				break;
    			case SORT_DATE:
    				$compare_function = 'return strtotime(strip_tags($a['.$column.'])) '.$compare_operator.' strtotime(strip_tags($b['.$column.']));';
    				break;
    			case SORT_STRING:
    			default:
    			    $compare_function = 'return api_strnatcmp(api_strtolower(strip_tags($a['.$column.'])), api_strtolower(strip_tags($b['.$column.']))) '.$compare_operator.' 0;';
    				break;
    		}		
        	// Sort the content
    		usort($data, create_function('$a, $b', $compare_function));
		}

		if (is_array($column_show)) {
			// We show only the columns data that were set up on the $column_show array
			$new_order_data = array();
			$count_data = count($data);
			$count_column_show = count($column_show);
			for ($j = 0; $j < $count_data; $j++) {
				$k = 0;
				for ($i = 0; $i < $count_column_show; $i++) {
					if ($column_show[$i]) {
						$new_order_data[$j][$k] = $data[$j][$i];
					}
					$k++;
				}
			}
			// Replace the multi-arrays
			$data = $new_order_data;
		}

		return $data;
	}

	/**
	 * Checks whether a column of a 2D-array contains only numeric values
	 * @param array $data		The data-array
	 * @param int $column		The index of the column to check
	 * @return bool				TRUE if column contains only dates, FALSE otherwise
	 * @todo Take locale into account (eg decimal point or comma ?)
	 * @author bart.mollet@hogent.be
	 */
	private function is_numeric_column(& $data, $column) {
		$is_numeric = true;
		foreach ($data as $index => & $row) {
			$is_numeric &= is_numeric(strip_tags($row[$column]));
			if (!$is_numeric) {
				break;
			}
		}
		return $is_numeric;
	}

	/**
	 * Checks whether a column of a 2D-array contains only dates (GNU date syntax)
	 * @param array $data		The data-array
	 * @param int $column		The index of the column to check
	 * @return bool				TRUE if column contains only dates, FALSE otherwise
	 * @author bart.mollet@hogent.be
	 */
	private function is_date_column(& $data, $column) {
		$is_date = true;
		foreach ($data as $index => & $row) {
			if (strlen(strip_tags($row[$column])) != 0) {
				$check_date = strtotime(strip_tags($row[$column]));
				// strtotime Returns a timestamp on success, FALSE otherwise.
				// Previous to PHP 5.1.0, this function would return -1 on failure.
				$is_date &= ($check_date != -1 && $check_date);
			} else {
				$is_date &= false;
			}
			if (!$is_date) {
				break;
			}
		}
		return $is_date;
	}

	/**
	 * Checks whether a column of a 2D-array contains only images (<img src="path/file.ext" alt=".."/>)
	 * @param array $data		The data-array
	 * @param int $column		The index of the column to check
	 * @return bool				TRUE if column contains only images, FALSE otherwise
	 * @author bart.mollet@hogent.be
	 */
	private function is_image_column(& $data, $column) {
		$is_image = true;
		foreach ($data as $index => & $row) {
			$is_image &= strlen(trim(strip_tags($row[$column], '<img>'))) > 0; // at least one img-tag
			$is_image &= strlen(trim(strip_tags($row[$column]))) == 0; // and no text outside attribute-values
			if (!$is_image) {
				break;
			}
		}
		return $is_image;
	}

}
