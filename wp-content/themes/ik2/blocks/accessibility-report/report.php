<?php
/**
 * WCAG 2.2 Level A and AA results shown by ik2/accessibility-report.
 *
 * Status is one of pass, fail, untested or na. Update this file after each test.
 *
 * @package IK2
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

return [
	'tested'     => '2026-10-23',
	'summary'    => [
		[ 'Website', 'ivankristianto.com' ],
		[ 'Standard', 'Web Content Accessibility Guidelines (WCAG) 2.2' ],
		[ 'Target level', 'Level AA' ],
		[ 'Current status', 'Partially conforms to Level AA' ],
		[ 'Build tested', 'The release that adds this page, tested on a local build before it went live. I will retest on the live site.' ],
		[ 'Views tested', 'Home, the articles archive, a long article with code and images, resume, about, contact, the 404 page and this page, each at 1440px and 390px, plus the command palette and the mobile menu. A content scan of all 490 posts for alt text, tables, embedded media, link text and headings.' ],
		[ 'Method', 'axe-core 4.10.2 with the WCAG 2.2 A and AA rules, a keyboard-only pass through every view, browser text size at 20 to 32px, 200% and 400% zoom, reflow at 320px, the WCAG text-spacing overrides, reduced motion, contrast computed from the theme colors, and a source review. No screen reader pass yet.' ],
		[ 'Tested by', 'Ivan Kristianto' ],
	],
	'principles' => [
		[
			'id'       => 'perceivable',
			'title'    => '1. Perceivable',
			'criteria' => [
				[ '1.1.1', 'Non-text Content', 'A', 'fail', 'Theme images and icons have text alternatives or are hidden as decoration. 31 images in 14 older posts, mostly screenshots and event photos, have no alt text. Six posts from 2010 and 2011 embed Flash players that no longer play and have no text alternative.' ],
				[ '1.2.1', 'Audio-only and Video-only (Prerecorded)', 'A', 'untested', '39 posts embed YouTube videos. I have not checked whether any of them is audio-only or video-only.' ],
				[ '1.2.2', 'Captions (Prerecorded)', 'A', 'untested', 'Captions on the 39 embedded YouTube videos are not checked yet.' ],
				[ '1.2.3', 'Audio Description or Media Alternative (Prerecorded)', 'A', 'untested', 'Not checked for the embedded videos yet.' ],
				[ '1.2.4', 'Captions (Live)', 'AA', 'na', 'There is no live audio or video.' ],
				[ '1.2.5', 'Audio Description (Prerecorded)', 'AA', 'untested', 'Not checked for the embedded videos yet.' ],
				[ '1.3.1', 'Info and Relationships', 'A', 'fail', 'Templates use landmarks, headings in order, real lists and labelled controls. One older post has a table of Google search operators whose header row uses ordinary cells. 50 older posts jump from the title to an h3; WCAG allows that, but I will fix it too.' ],
				[ '1.3.2', 'Meaningful Sequence', 'A', 'untested', 'Source order matches the visual order in every template. Still to confirm with a screen reader.' ],
				[ '1.3.3', 'Sensory Characteristics', 'A', 'untested', 'Theme copy never relies on shape, size or position. Instructions in older posts are not reviewed yet.' ],
				[ '1.3.4', 'Orientation', 'AA', 'pass', 'Nothing locks the orientation. Every view works in portrait and landscape.' ],
				[ '1.3.5', 'Identify Input Purpose', 'AA', 'na', 'The only field is search, which asks nothing about you.' ],
				[ '1.4.1', 'Use of Color', 'A', 'pass', 'Links in running text are underlined. Status labels, filters and the current page all carry text, not color alone.' ],
				[ '1.4.2', 'Audio Control', 'A', 'na', 'Nothing plays audio on its own.' ],
				[ '1.4.3', 'Contrast (Minimum)', 'AA', 'pass', 'Every text color clears 4.5:1 on each background it sits on. The lowest pair is 5.75:1.' ],
				[ '1.4.4', 'Resize Text', 'AA', 'pass', 'All type is set in rem and follows the browser text size. With the default at 20 to 32px and at 200% zoom, nothing is cut off or scrolls sideways.' ],
				[ '1.4.5', 'Images of Text', 'AA', 'pass', 'The theme renders all text as text. Screenshots in posts show software as it appears, which the criterion allows.' ],
				[ '1.4.10', 'Reflow', 'AA', 'pass', 'No view scrolls sideways at 320px. Code blocks and tables wrap instead.' ],
				[ '1.4.11', 'Non-text Contrast', 'AA', 'pass', 'The focus ring measures 7.29:1 against the page. Controls are identified by their text, so their light borders carry no meaning on their own.' ],
				[ '1.4.12', 'Text Spacing', 'AA', 'pass', 'With the WCAG spacing overrides applied, no text is cut off or overlaps on any view.' ],
				[ '1.4.13', 'Content on Hover or Focus', 'AA', 'na', 'Nothing appears on hover or focus.' ],
			],
		],
		[
			'id'       => 'operable',
			'title'    => '2. Operable',
			'criteria' => [
				[ '2.1.1', 'Keyboard', 'A', 'pass', 'Navigation, the command palette, the mobile menu, the article filters and the image lightbox all work from the keyboard.' ],
				[ '2.1.2', 'No Keyboard Trap', 'A', 'pass', 'Tab reaches the end of every view. The mobile menu and the palette hold focus while open and release it on Escape.' ],
				[ '2.1.4', 'Character Key Shortcuts', 'A', 'na', 'The only shortcut is Cmd+K or Ctrl+K, which needs a modifier key.' ],
				[ '2.2.1', 'Timing Adjustable', 'A', 'na', 'Nothing has a time limit.' ],
				[ '2.2.2', 'Pause, Stop, Hide', 'A', 'untested', 'The blinking cursor and status dots stop within five seconds, and nothing blinks with reduced motion on. Ten older posts include GIFs that are not checked yet.' ],
				[ '2.3.1', 'Three Flashes or Below Threshold', 'A', 'untested', 'The theme never flashes. The GIFs in ten older posts are not checked yet.' ],
				[ '2.4.1', 'Bypass Blocks', 'A', 'pass', 'A skip link is the first stop and moves focus to the main content.' ],
				[ '2.4.2', 'Page Titled', 'A', 'pass', 'Every view has a title that names it, such as "Resume - Ivan" and "Page not found - Ivan".' ],
				[ '2.4.3', 'Focus Order', 'A', 'pass', 'Focus follows the visual order. On phones the Resume button comes before the menu button in both the layout and the tab order.' ],
				[ '2.4.4', 'Link Purpose (In Context)', 'A', 'untested', 'Theme links name where they go. 97 links in 75 older posts read "here" or "click here"; most sentences give the context, but I have not checked each one.' ],
				[ '2.4.5', 'Multiple Ways', 'AA', 'pass', 'Main navigation, the command palette search, topic and tag archives, and the footer.' ],
				[ '2.4.6', 'Headings and Labels', 'AA', 'pass', 'Headings and labels describe their section or control.' ],
				[ '2.4.7', 'Focus Visible', 'AA', 'pass', 'Every focus stop shows a 2px ring in the accent color with a 3px offset.' ],
				[ '2.4.11', 'Focus Not Obscured (Minimum)', 'AA', 'pass', 'No sticky header or banner covers the focused element. While the mobile menu is open, focus stays inside it.' ],
				[ '2.5.1', 'Pointer Gestures', 'A', 'na', 'Nothing needs a multi-point or path-based gesture.' ],
				[ '2.5.2', 'Pointer Cancellation', 'A', 'pass', 'Links and buttons act on release, the browser default.' ],
				[ '2.5.3', 'Label in Name', 'A', 'pass', 'Each accessible name contains the visible label. The menu button reads "menu" and is named "Open menu".' ],
				[ '2.5.4', 'Motion Actuation', 'A', 'na', 'Nothing responds to device motion.' ],
				[ '2.5.7', 'Dragging Movements', 'AA', 'na', 'Nothing is dragged.' ],
				[ '2.5.8', 'Target Size (Minimum)', 'AA', 'pass', 'Most targets are 44px tall. The 32px footer icons and filter pills meet the spacing exception.' ],
			],
		],
		[
			'id'       => 'understandable',
			'title'    => '3. Understandable',
			'criteria' => [
				[ '3.1.1', 'Language of Page', 'A', 'pass', 'Every page declares English (en-US).' ],
				[ '3.1.2', 'Language of Parts', 'AA', 'pass', 'Posts are in English. Indonesian event and company names are proper names, which the criterion exempts.' ],
				[ '3.2.1', 'On Focus', 'A', 'pass', 'Focusing a control never opens anything or moves the page.' ],
				[ '3.2.2', 'On Input', 'A', 'pass', 'The article filters are links, so nothing changes until you follow one.' ],
				[ '3.2.3', 'Consistent Navigation', 'AA', 'pass', 'Every view shares one header and footer, in the same order.' ],
				[ '3.2.4', 'Consistent Identification', 'AA', 'pass', 'Controls that do the same thing have the same name everywhere.' ],
				[ '3.2.6', 'Consistent Help', 'A', 'pass', 'Contact sits in the main navigation of every page, in the same place.' ],
				[ '3.3.1', 'Error Identification', 'A', 'na', 'No field can produce an input error.' ],
				[ '3.3.2', 'Labels or Instructions', 'A', 'pass', 'The search field in the command palette is labelled.' ],
				[ '3.3.3', 'Error Suggestion', 'AA', 'na', 'No field can produce an input error.' ],
				[ '3.3.4', 'Error Prevention (Legal, Financial, Data)', 'AA', 'na', 'There are no legal, financial or data forms.' ],
				[ '3.3.7', 'Redundant Entry', 'A', 'na', 'Nothing asks for the same information twice.' ],
				[ '3.3.8', 'Accessible Authentication (Minimum)', 'AA', 'na', 'Public pages have no login.' ],
			],
		],
		[
			'id'       => 'robust',
			'title'    => '4. Robust',
			'criteria' => [
				[ '4.1.2', 'Name, Role, Value', 'A', 'untested', 'axe finds no name, role or value problems, and the keyboard checks pass. Still to confirm with a screen reader.' ],
				[ '4.1.3', 'Status Messages', 'AA', 'pass', 'The command palette announces its result count, such as "8 results.", without moving focus.' ],
			],
		],
	],
	'next_steps' => [
		'Write alt text for the 31 images and replace the six Flash embeds.',
		'Add header cells to the search operators table.',
		'Check the GIFs, and the captions on the embedded videos.',
		'Review link text and instructions in older posts.',
		'Test with VoiceOver and NVDA.',
		'Retest on the live site after this release and update this page.',
	],
];
