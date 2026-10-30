import assert from 'node:assert/strict';
import test from 'node:test';

import {
	dateRange,
	flattenGa4Rows,
	flattenGscRows,
	ga4DimensionFilter,
	gscFilterGroups,
	parseFilter,
} from '../.claude/skills/blog-analytics/fetch.mjs';

const today = new Date( '2026-10-30T09:00:00Z' );

test( 'the default range ends lagDays before today and spans --days days', () => {
	assert.deepEqual( dateRange( { days: '28' }, 3, today ), {
		startDate: '2026-09-30',
		endDate: '2026-10-27',
	} );
} );

test( 'an explicit start and end pass through unchanged', () => {
	assert.deepEqual(
		dateRange(
			{ start: '2026-01-01', end: '2026-01-31', days: '28' },
			3,
			today
		),
		{
			startDate: '2026-01-01',
			endDate: '2026-01-31',
		}
	);
} );

test( '--days counts back from an explicit end', () => {
	assert.deepEqual( dateRange( { end: '2026-03-01', days: '7' }, 3, today ), {
		startDate: '2026-02-23',
		endDate: '2026-03-01',
	} );
} );

test( 'a non-numeric --days is rejected', () => {
	assert.throws( () => dateRange( { days: 'month' }, 3, today ), /--days/ );
} );

test( 'a lag of zero ends the range today', () => {
	assert.equal( dateRange( { days: '7' }, 0, today ).endDate, '2026-10-30' );
} );

test( 'malformed and impossible dates are rejected by flag name', () => {
	assert.throws(
		() => dateRange( { start: '2026/01/01', days: '28' }, 3, today ),
		/--start/
	);
	assert.throws(
		() => dateRange( { end: '2026-02-30', days: '28' }, 3, today ),
		/--end/
	);
} );

test( 'a start after the end is rejected', () => {
	assert.throws(
		() =>
			dateRange(
				{ start: '2026-03-01', end: '2026-02-01', days: '28' },
				3,
				today
			),
		/after end date/
	);
} );

test( 'a filter value may contain colons', () => {
	assert.deepEqual(
		parseFilter( 'page:equals:https://www.ivankristianto.com/' ),
		{
			dimension: 'page',
			operator: 'equals',
			value: 'https://www.ivankristianto.com/',
		}
	);
} );

test( 'an unknown filter operator is rejected', () => {
	assert.throws(
		() => parseFilter( 'page:startsWith:/x' ),
		/operator one of/
	);
} );

test( 'filters become one AND group for Search Console', () => {
	assert.deepEqual(
		gscFilterGroups( [
			'page:contains:/wordpress',
			'query:notRegex:ivan',
		] ),
		[
			{
				groupType: 'and',
				filters: [
					{
						dimension: 'page',
						operator: 'contains',
						expression: '/wordpress',
					},
					{
						dimension: 'query',
						operator: 'excludingRegex',
						expression: 'ivan',
					},
				],
			},
		]
	);
	assert.equal( gscFilterGroups( [] ), undefined );
} );

test( 'a single GA4 filter is not wrapped, negations use notExpression', () => {
	assert.deepEqual( ga4DimensionFilter( [ 'pagePath:notContains:/tag/' ] ), {
		notExpression: {
			filter: {
				fieldName: 'pagePath',
				stringFilter: { matchType: 'CONTAINS', value: '/tag/' },
			},
		},
	} );
	assert.ok(
		ga4DimensionFilter( [
			'pagePath:contains:/a',
			'country:equals:Indonesia',
		] ).andGroup
	);
} );

test( 'Search Console keys are named after the requested dimensions', () => {
	assert.deepEqual(
		flattenGscRows(
			[
				{
					keys: [ 'wp cli', '/wp-cli/' ],
					clicks: 3,
					impressions: 90,
					ctr: 0.033,
					position: 7.2,
				},
			],
			[ 'query', 'page' ]
		),
		[
			{
				query: 'wp cli',
				page: '/wp-cli/',
				clicks: 3,
				impressions: 90,
				ctr: 0.033,
				position: 7.2,
			},
		]
	);
} );

test( 'GA4 rows are keyed by header name with numeric metrics', () => {
	const report = {
		dimensionHeaders: [ { name: 'pagePath' } ],
		metricHeaders: [
			{ name: 'screenPageViews', type: 'TYPE_INTEGER' },
			{ name: 'averageSessionDuration', type: 'TYPE_SECONDS' },
		],
		rows: [
			{
				dimensionValues: [ { value: '/' } ],
				metricValues: [ { value: '120' }, { value: '41.5' } ],
			},
		],
	};
	assert.deepEqual( flattenGa4Rows( report ), [
		{ pagePath: '/', screenPageViews: 120, averageSessionDuration: 41.5 },
	] );
	assert.deepEqual( flattenGa4Rows( {} ), [] );
} );
