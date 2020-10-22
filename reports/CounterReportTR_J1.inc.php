<?php

import('plugins.generic.counterSushi.CounterReport');

class CounterReportTR_J1 extends CounterReport{
	
	/*
	 * Provide the tr_j1 Counter report
	 * @params $args Contains the list of parsed arguments to fetch statistics
	 * @return array 
	 */
	public function getReport ($args) {
		$totals = Services::get('stats')
				->getOrderedObjects(
						STATISTICS_DIMENSION_CONTEXT_ID,
						STATISTICS_ORDER_DESC,
						$args
					);
		$rows = [];
		foreach ($totals as $total) {
			$context = \Services::get('context')->get($total['id']);
			if (!$context)
				continue;
			$rows[] = [
				'Title' => $context->getLocalizedData('name'),
				'Item_ID' => [
					[
						'Type' => 'Print_ISSN',
						'Value' => $context->getData('printIssn'),
					],
					[
						'Type' => 'Online_ISSN',
						'Value' => $context->getData('onlineIssn'),
					],
				],
				'Platform' => '...',
				'Publisher' => $context->getData('publisherInstitution'),
				'Performance' => [
					[
						'Period' => [
							'Begin_Date' => $args['dateStart'],
							'End_Date' => $args['dateEnd'],
						],
						'Instance' => [
							[
								'MetricType' => 'Unique_Item_Request',
								'Count' => $total['total'],
							],
						],
					],
				]
			];	
		}
		return $rows;
	}
}
