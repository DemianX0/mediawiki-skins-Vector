<?php
/**
 * Vector - Modern version of MonoBook with fresh look and many usability
 * improvements.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @ingroup Skins
 */

use MediaWiki\MediaWikiServices;
use Vector\Constants;
use Vector\Hooks;
use Wikimedia\WrappedString;

/**
 * Skin subclass for Vector
 * @ingroup Skins
 * @final skins extending SkinVector are not supported
 * @unstable
 */
class SkinVector extends SkinTemplate {
	public $skinname = Constants::SKIN_NAME;
	public $stylename = 'Vector';
	public $template = 'VectorTemplate';
	public $contentTOC;

	public $localizedMessages = [
			'tagline',
			'sitetitle',
			'sitesubtitle',
			'toc-screen-reader',
			'navigation-heading',
			'article-toolbar-screen-reader',
			'vector-jumptocontent',
			'vector-jumptonavigation',
			'vector-jumptosearch',
			'vector-action-toggle-sidebar',
	];

	static ?ParserOutput $parserOutput = null;

	/**
	 * @inheritDoc
	 * @param array|null $options Note; this param is only optional for internal purpose.
	 * 		Do not instantiate Vector, use SkinFactory to create the object instead.
	 * 		If you absolutely must to, this paramater is required; you have to provide the
	 * 		skinname with the `name` key. That's do it with `new SkinVector( ['name' => 'vector'] )`.
	 * 		Failure to do that, will lead to fatal exception.
	 */
	public function __construct( $options = [] ) {
		// Add meta viewport tag for mobile viewing in readable size at 1.0 scale.
		$options['responsive'] = [
			// Fixed and sticky elements on Chrome mobile are not properly fixed.
			// Scrolling moves them off-screen, which could be used as a header-hiding feature,
			// but the element is not even moved 100% off-screen, only partially,
			// resulting in a confusing, partially-obscured header.
			// Unfortunately disabling this awkward quirk comes at the price of disabling zoom out on mobile.
			// https://stackoverflow.com/a/44680066
			// 'minimum-scale=1' prevents fixed elements sliding off-screen on mobile.
			'minimum-scale' => '1.0',
		];
		$options['templateDirectory'] = __DIR__ . '/templates';
		parent::__construct( $options );
	}

	/**
	 * @inheritDoc
	 * @return array
	 */
	public function getDefaultModules() {
		$modules = parent::getDefaultModules();

		if ( $this->isLegacy() ) {
			$modules['styles']['skin'][] = 'skins.vector.styles.legacy';
			$modules[Constants::SKIN_NAME] = 'skins.vector.legacy.js';
		} else {
			$modules['styles'] = array_merge(
				$modules['styles'], [
					'skins.vector.styles',
					'mediawiki.ui.icon',
					'skins.vector.icons',
					// Icons from:  https://doc.wikimedia.org/oojs-ui/master/demos/?page=icons&theme=wikimediaui&direction=ltr&platform=desktop
					// Classes: '.mw-ui-icon-userAvatar', etc.
					'oojs-ui.styles.icons-alerts', // message, speechBubbles
					'oojs-ui.styles.icons-content', // article, history
					'oojs-ui.styles.icons-editing-advanced', // markup, wikiText
					'oojs-ui.styles.icons-editing-core', // edit, link
					'oojs-ui.styles.icons-editing-list', // listBullet, listNumbered
					'oojs-ui.styles.icons-interactions', // logIn, logOut, settings
					'oojs-ui.styles.icons-layout', // menu, recentChanges, textFlow, (stripeToC removed)
					'oojs-ui.styles.icons-location', // globe
					'oojs-ui.styles.icons-user', // userAnonymous, userAvatar, userAvatarOutline, userContributions, userTalk
					'oojs-ui.styles.icons-wikimedia', // logoWikipedia
				]
			);
			$modules[Constants::SKIN_NAME][] = 'skins.vector.js';
		}

		return $modules;
	}

	/**
	 * Set up the VectorTemplate. Overrides the default behaviour of SkinTemplate allowing
	 * the safe calling of constructor with additional arguments. If dropping this method
	 * please ensure that VectorTemplate constructor arguments match those in SkinTemplate.
	 *
	 * @internal
	 * @param string $classname
	 * @return VectorTemplate
	 */
	protected function setupTemplate( $classname ) {
		$tp = new TemplateParser( __DIR__ . '/templates' );
		return new VectorTemplate( $this->getConfig(), $tp, $this->isLegacy() );
	}

	/**
	 * Whether or not the legacy version of the skin is being used.
	 *
	 * @return bool
	 */
	private function isLegacy() : bool {
		$isLatestSkinFeatureEnabled = MediaWikiServices::getInstance()
			->getService( Constants::SERVICE_FEATURE_MANAGER )
			->isFeatureEnabled( Constants::FEATURE_LATEST_SKIN );

		return !$isLatestSkinFeatureEnabled;
	}

	/**
	 * @internal only for use inside VectorTemplate
	 * @return array of data for a Mustache template
	 */
	public function getTemplateData() {
		$out = $this->getOutput();
		$title = $out->getTitle();

		$indicators = [];
		foreach ( $out->getIndicators() as $id => $content ) {
			$indicators[] = [
				'id' => Sanitizer::escapeIdForAttribute( "mw-indicator-$id" ),
				'class' => 'mw-indicator',
				'html' => $content,
			];
		}

		$printFooter = Html::rawElement(
			'div',
			[ 'class' => 'printfooter' ],
			$this->printSource()
		);

		$data = [
			// Data objects:
			'array-indicators' => $indicators,
			// HTML strings:
			'html-printtail' => WrappedString::join( "\n", [
				MWDebug::getHTMLDebugLog(),
				MWDebug::getDebugHTML( $this->getContext() ),
				$this->bottomScripts(),
				wfReportTime( $out->getCSP()->getNonce() )
			] ) . '</body></html>',
			'html-site-notice' => $this->getSiteNotice(),
			'html-user-language-attributes' => $this->prepareUserLanguageAttributes(),
			'html-subtitle' => $this->prepareSubtitle(),
			// Always returns string, cast to null if empty.
			'html-undelete-link' => $this->prepareUndeleteLink() ?: null,
			// Result of OutputPage::addHTML calls
			'html-body-content' => $this->wrapHTML( $title, $out->mBodytext ) . $printFooter,
			'html-after-content' => $this->afterContentHook(),
		];

		if ( !$this->isLegacy() && !!self::$parserOutput ) {
			$data['data-toc'] = $this->getTOCData( self::$parserOutput );
		}

		return $data;
	}

	/**
	 * @param $parserOutput object
	 * @return array
	 */
	private function getTOCData( ParserOutput $parserOutput ) : ?array {
		$tocdata = [];
		$this->contentTOC = $parserOutput->getTOCHTML();
		if ( $this->contentTOC ) {
			$tocdata['html-body-content-toc'] = $this->wrapHTML( $this->getOutput()->getTitle(), $this->contentTOC, false );
		}

		//$maxTocLevel = MediaWikiServices::getInstance()->getMainConfig()->get( 'MaxTocLevel' ); // 999, pointless
		$tocdata['has-items'] = false;
		$tocdata['data-items'] = [];
		$prevlevel = 1;
		$currentParent = &$tocdata['data-items'];
		//$last = &$currentParent;
		$parents = [ null, &$currentParent ];
		foreach ( $parserOutput->getSections() as $tocitem ) {
			$toclevel = $tocitem['toclevel'];
			if ( $toclevel < $prevlevel ) {
				$currentParent = &$parents[ $toclevel ];
			} elseif ( $toclevel > $prevlevel ) {
				$dataitem = &$currentParent[ count( $currentParent ) - 1 ];
				$dataitem['has-items'] = true;
				$currentParent = &$dataitem['data-items'];
			}

			$currentParent[] = [
				'has-items' => false,
				'data-items' => false, // Avoid recursion on inherited subitems.
				'id' => $tocitem['anchor'],
				'number' => $tocitem['number'],
				'title' => $tocitem['line'],
			];
			/*
			'toclevel' => $toclevel,
			'level' => $level,
			'line' => $tocline,
			'number' => $numbering,
			'index' => ( $isTemplate ? 'T-' : '' ) . $sectionIndex,
			'fromtitle' => $titleText,
			'byteoffset' => ( $noOffset ? null : $byteOffset ),
			'anchor' => $anchor,
			*/
		}
		return !empty( $tocdata['data-items'] ) ? $tocdata : null;
	}

	/**
	 * @internal only for use inside VectorTemplate
	 * @return array
	 */
	public function getMenuProps() {
		return $this->buildContentNavigationUrls();
	}
}
