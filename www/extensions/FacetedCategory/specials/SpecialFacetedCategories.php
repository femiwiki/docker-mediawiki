<?php
/**
 * Implements Special:Categories
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
 * @ingroup SpecialPage
 */

/**
 * @ingroup SpecialPage
 */
class SpecialFacetedCategories extends SpecialPage {

	protected $linkRenderer = null;

	public function __construct() {
		parent::__construct( 'FacetedCategories' );

		// Since we don't control the constructor parameters, we can't inject services that way.
		// Instead, we initialize services in the execute() method, and allow them to be overridden
		// using the initServices() method.
	}
    
    public function setPageLinkRenderer(
        PageLinkRenderer $linkRenderer
    ) {
        $this->linkRenderer = $linkRenderer;
    }
    
private function initServices() {
    if ( !$this->linkRenderer ) {
        $lang = $this->getContext()->getLanguage();
        $titleFormatter = new MediaWikiTitleCodec( $lang, GenderCache::singleton() );
        $this->linkRenderer = new MediaWikiPageLinkRenderer( $titleFormatter );
    }
}

	public function execute( $par ) {
		$this->initServices();

		$this->setHeaders();
		$this->outputHeader();
		$this->getOutput()->allowClickjacking();

		$facetName = $this->getRequest()->getText( 'facetName', $par );
		$facetMember = $this->getRequest()->getText( 'facetMember', $par );

		$cap = new FacetedCategoryPager(
			$this->getContext(),
			$facetName,
			$facetMember,
			$this->linkRenderer
		);
		$cap->doQuery();

		$this->getOutput()->addHTML(
			Html::openElement( 'div', [ 'class' => 'mw-spcontent' ] ) .
				$this->msg( 'categoriespagetext', $cap->getNumRows() )->parseAsBlock() .
				$cap->getStartForm( $facetName, $facetMember ) .
				$cap->getNavigationBar() .
				'<ul>' . $cap->getBody() . '</ul>' .
				$cap->getNavigationBar() .
				Html::closeElement( 'div' )
		);
	}

	protected function getGroupName() {
		return 'pages';
	}
}
