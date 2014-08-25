<?php

	/****************************************************************************/
	/****** Select Region ID based on zip and state *****************************/
	/****************************************************************************/
	/*
	    Select region based on distance.
	    Main goal here is that we can select a technician that might be in a
	    different state from where the service destination is located.
	    i.e. When selecting a technician we look for people not only in the
			destination state, but also in the adjacent states.
	****************************************************************************/

	function get_region_id($zip, $state)
	{
		global $db, $testing;

		if (!$zip || !$state) return 0;

		if (strlen($state) == 2) {
			$state_abbrev = strtoupper($state);
			$state = state_fullname($state_abbrev);
		}
		else {
			$state = ucfirst($state);
			$states_full_abbrv = states_full_abbrv();
			$state_abbrev = $states_full_abbrv[$state];
		}

		$regions = $db->get_results("SELECT state,region FROM regions ORDER BY state",ARRAY_A);

		$r_by_state =array();

		foreach($regions as $r) {
			$r_by_state[$r['state']][] = $r['region'];
		}

		$region_id = get_region_inclusive($zip, $state, $state_abbrev, $r_by_state);

		return $region_id ? $region_id : 0;
	}


	function get_region_inclusive($zip, $state, $state_abbrev, $r_by_state)
	{
		global $db;
			
		$states_full_abbrv = states_full_abbrv();
		$adjacent = adjacent_states();

		unset($r_by_state['default']);
		$ocs_states = array_keys($r_by_state);

		array_unshift($adjacent[$state], $state);

		foreach($adjacent[$state] as $st) {
			if (in_array($st, $ocs_states)) {
				foreach($r_by_state[$st] as $region) {
					$destinations[] = "$region,".$states_full_abbrv[$st].",US";
				}
			}
		}
		$index = get_min_distance($zip, $destinations, GOOGLE_API);

		if ($index >= 0) {
			list($region, $state_region) = explode(',', $destinations[$index]);
			$state_region = state_fullname($state_region);
			return $db->get_var("SELECT id FROM regions WHERE region = '$region' AND state = '$state_region'");
		}
		return 0;
	}

?>
