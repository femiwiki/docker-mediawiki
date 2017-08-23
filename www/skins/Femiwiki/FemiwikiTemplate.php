<?php

/**
 * BaseTemplate class for the Femiwiki skin
 *
 * @ingroup Skins
 */
class FemiwikiTemplate extends BaseTemplate
{
    protected static $googleApiKey = 'AIzaSyC3vDxqg6zA-f8qU--V488nngsBYnZZgPc';
    /**
     * Outputs the entire contents of the page
     */
    public function execute()
    {
        $this->html('headelement');
        ?>
        <!-- Google Tag Manager (noscript) -->
        <noscript>
            <iframe src="https://www.googletagmanager.com/ns.html?id=GTM-5TNKVJ" height="0" width="0"
                    style="display:none;visibility:hidden"></iframe>
        </noscript>
        <!-- End Google Tag Manager (noscript) -->

        <div id="mw-wrapper" class="<?php echo $_GET['classes'] ?>">
            <div class="nav-bar">
                <div id="mw-navigation">
                    <h1 id="p-logo">
                        <a href="/" class="mw-wiki-logo"><img src="/skins/Femiwiki/images/logo-long.png" alt="Femiwiki"></a>
                    </h1>

                    <?php
                    echo Html::rawElement(
                        'h2',
                        [],
                        $this->getMsg('navigation-heading')->parse()
                    );
                    ?>

                    <button id="fw-menu-toggle">
                        <span class="icon"></span>
                        <span class="badge"></span>
                    </button>

                    <ul id="site-navigation">
                        <li class="changes"><a href="/w/Special:RecentChanges" title="바뀐글"><span class="text">바뀐글</span></a></li>
                        <li class="random"><a href="/w/Special:RandomPage" title="임의글"><span class="text">임의글</span></a></li>
                    </ul>

                    <?php
                    echo $this->getSearch();
                    ?>
                </div>
            </div>

            <div id="fw-menu">
                <?php
                // User profile links
                echo $this->getUserLinks();
                $this->renderPortals( $this->data['sidebar'] );
                ?>
            </div>

            <?php

            echo Html::openElement(
                'div',
                array('id' => 'p-navigation-and-watch')
            );
            if ($this->data['sitenotice']) {
                echo Html::rawElement(
                    'div',
                    array('id' => 'siteNotice'),
                    $this->get('sitenotice')
                );
            }
            if ($this->data['newtalk']) {
                echo Html::rawElement(
                    'div',
                    array('class' => 'usermessage'),
                    $this->get('newtalk')
                );
            }
            //echo $this->getIndicators();
            echo $this->getPortlet(array(
                'id' => 'p-namespaces',
                'headerMessage' => 'namespaces',
                'content' => $this->data['content_navigation']['namespaces'],
            ));
            echo $this->getWatch();
            echo Html::closeElement('div');
            ?>

            <div id="content" class="mw-body" role="main">
                <?php
                echo Html::openElement(
                    'div',
                    array('id' => 'p-header')
                );


                echo Html::openElement(
                    'div',
                    array('id' => 'p-title-and-tb')
                );
                echo Html::rawElement(
                    'h1',
                    array(
                        'class' => 'firstHeading',
                        'lang' => $this->get('pageLanguage')
                    ),
                    $this->get('title')
                );
                ?>
                <button id='p-links-toggle'>
                    <span id='p-links-toggle-text'>•••</span>
                </button>
                <?php
                echo Html::openElement(
                    'div',
                    array('id' => 'p-actions-and-toolbox')
                );
                echo $this->renderPortal('page-tb', $this->getToolbox(), 'toolbox');
                if (isset( $this->data['articleid']) && $this->data['articleid'] != 0 )
                    echo $this->renderPortal('share-tb', $this->getShareToolbox(), '공유하기', 'SkinTemplateToolboxEnd');
                echo $this->getPortlet(array(
                    'id' => 'p-actions',
                    'headerMessage' => 'actions',
                    'content' => $this->data['content_navigation']['actions'],
                ));

                echo Html::closeElement('div');
                echo Html::closeElement('div');
                echo Html::openElement(
                    'div',
                    array('id' => 'lastmod-and-views')
                );

                echo Html::rawElement(
                        'a',
                        array(
                            'id' => 'lastmod',
                            'href' => '/index.php?title='.$this->getSkin()->getRelevantTitle().'&action=history'
                        ),
                        $this->get('lastmod')
                        );

                unset( $this->data['content_navigation']['views']['history'] );
                echo $this->getPortlet(array(
                    'id' => 'p-views',
                    'headerMessage' => 'views',
                    'content' => $this->data['content_navigation']['views'],
                ));
                
                echo Html::closeElement('div');
                echo Html::closeElement('div');
                ?>

                <div class="mw-body-content" id="bodyContent">
                    <?php
                    echo Html::openElement(
                        'div',
                        array('id' => 'contentSub')
                    );
                    if ($this->data['subtitle']) {
                        echo Html::rawelement(
                            'p',
                            [],
                            $this->get('subtitle')
                        );
                    }
                    echo Html::rawelement(
                        'p',
                        [],
                        $this->get('undelete')
                    );
                    echo Html::closeElement('div');

                    $this->html('bodycontent');
                    $this->clear();
                    echo Html::rawElement(
                        'div',
                        array('class' => 'printfooter'),
                        $this->get('printfooter')
                    );
                    $this->html('catlinks');
                    $this->html('dataAfterContent');
                    ?>
                </div>
            </div>

            <?php
            $this->set( 'reportinfringement', $this->getSkin()->footerLink( 'reportinfringement', 'reportinfringementpage' ) );
            $this->data['footerlinks']['places'][] = 'reportinfringement';
            ?>

            <div id="mw-footer">
                <div id="mw-footer-bar"></div>

                <ul id="fw-footer-menu"></ul>
                <?php
                echo Html::openElement(
                    'ul',
                    array(
                        'id' => 'footer-icons',
                        'role' => 'contentinfo'
                    )
                );
                foreach ($this->getFooterIcons('icononly') as $blockName => $footerIcons) {
                    echo Html::openElement(
                        'li',
                        array(
                            'id' => 'footer-' . Sanitizer::escapeId($blockName) . 'ico'
                        )
                    );
                    foreach ($footerIcons as $icon) {
                        echo $this->getSkin()->makeFooterIcon($icon);
                    }
                    echo Html::closeElement('li');
                }
                echo Html::closeElement('ul');

                foreach ($this->getFooterLinks() as $category => $links) {
                    echo Html::openElement(
                        'ul',
                        array(
                            'id' => 'footer-' . Sanitizer::escapeId($category),
                            'role' => 'contentinfo'
                        )
                    );
                    foreach ($links as $key) {
                        if($key === 'lastmod') continue;
                        echo Html::rawElement(
                            'li',
                            array(
                                'id' => 'footer-' . Sanitizer::escapeId($category . '-' . $key)
                            ),
                            $this->get($key)
                        );
                    }
                    echo Html::closeElement('ul');
                }
                $this->clear();
                ?>
            </div>
        </div>

        <?php $this->printTrail() ?>

        </body>
        </html>

        <?php
    }

    /**
     * Generates a single sidebar portlet of any kind
     * @return string html
     */
    private function getPortlet($box)
    {
        if (!$box['content']) {
            return;
        }

        $html = Html::openElement(
            'div',
            array(
                'role' => 'navigation',
                'class' => 'mw-portlet',
                'id' => Sanitizer::escapeId($box['id'])
            ) + Linker::tooltipAndAccesskeyAttribs($box['id'])
        );
        $html .= Html::element(
            'h3',
            [],
            isset($box['headerMessage']) ? $this->getMsg($box['headerMessage'])->text() : $box['header']);
        if (is_array($box['content'])) {
            $html .= Html::openElement('ul');
            foreach ($box['content'] as $key => $item) {
                $html .= $this->makeListItem($key, $item);
            }
            $html .= Html::closeElement('ul');
        } else {
            $html .= $box['content'];
        }
        $html .= Html::closeElement('div');

        return $html;
    }

    /**
     * Generates the logo and (optionally) site title
     * @return string html
     */
    private function getLogo($id = 'p-logo', $imageOnly = false)
    {
        $html = Html::openElement(
            'div',
            array(
                'id' => $id,
                'class' => 'mw-portlet',
                'role' => 'banner'
            )
        );
        $html .= Html::element(
            'a',
            array(
                'href' => $this->data['nav_urls']['mainpage']['href'],
                'class' => 'mw-wiki-logo',
            ) + Linker::tooltipAndAccesskeyAttribs('p-logo')
        );
        if (!$imageOnly) {
            $html .= Html::element(
                'a',
                array(
                    'id' => 'p-banner',
                    'class' => 'mw-wiki-title',
                    'href' => $this->data['nav_urls']['mainpage']['href']
                ) + Linker::tooltipAndAccesskeyAttribs('p-logo'),
                $this->getMsg('sitetitle')->escaped()
            );
        }
        $html .= Html::closeElement('div');

        return $html;
    }

    private function getWatch() {
        $nav = $this->data['content_navigation'];
        $mode = $this->getSkin()->getUser()->isWatched( $this->getSkin()->getRelevantTitle() )
            ? 'unwatch'
            : 'watch';
        if ( isset( $nav['actions'][$mode] ) ) {
            $nav['views'][$mode] = $nav['actions'][$mode];
            $nav['views'][$mode]['class'] = rtrim( 'icon ' . $nav['views'][$mode]['class'], ' ' );
            $nav['views'][$mode]['primary'] = true;
            unset( $this->data['content_navigation']['actions'][$mode] );
            $item = $nav['actions'][$mode];
            $attrs = [];
            $attrs['class'] = 'mw-portlet';
            $attrs['id'] = 'ca-watch';
            
            return Html::rawElement( 'span', $attrs, $this->makeLink( $mode, $item, $options ) );
        }
    }

    /**
     * Generates the search form
     * @return string html
     */
    private function getSearch()
    {
        $html = Html::openElement(
            'form',
            array(
                'action' => htmlspecialchars($this->get('wgScript')),
                'role' => 'search',
                'class' => 'mw-portlet',
                'id' => 'p-search'
            )
        );
        $html .= Html::hidden('title', htmlspecialchars($this->get('searchtitle')));
        $html .= Html::rawelement(
            'h3',
            [],
            Html::label($this->getMsg('search')->escaped(), 'searchInput')
        );
        $html .= $this->makeSearchInput(array('id' => 'searchInput'));
        $html .= Html::rawelement(
            'button',
            [
                'id'=>'searchClearButton',
                'type' => 'button'
            ],
            '×'
        );
        $html .= $this->makeSearchButton('go', array('id' => 'searchGoButton', 'class' => 'searchButton'));
        $html .= Html::closeElement('form');

        return $html;
    }

    function makeSearchInput( $attrs = [] ) {
        $realAttrs = [
            'type' => 'search',
            'name' => 'search',
            'placeholder' => wfMessage( 'searchsuggest-search' )->text(),
            'value' => $this->get( 'search', '' ),
        ];
        //if($realAttrs[value]==null) $realAttrs[value] = str_replace( '_', ' ', $this->get('titleprefixeddbkey'));
        $realAttrs = array_merge( $realAttrs, Linker::tooltipAndAccesskeyAttribs( 'search' ), $attrs );
        return Html::element( 'input', $realAttrs );
    }

    function getToolbox() {
        $toolbox = [];

        if ( isset( $this->data['nav_urls']['whatlinkshere'] )
            && $this->data['nav_urls']['whatlinkshere']
        ) {
            $toolbox['whatlinkshere'] = $this->data['nav_urls']['whatlinkshere'];
            $toolbox['whatlinkshere']['id'] = 't-whatlinkshere';
        }
        if ( isset( $this->data['nav_urls']['recentchangeslinked'] )
            && $this->data['nav_urls']['recentchangeslinked']
        ) {
            $toolbox['recentchangeslinked'] = $this->data['nav_urls']['recentchangeslinked'];
            $toolbox['recentchangeslinked']['msg'] = 'recentchangeslinked-toolbox';
            $toolbox['recentchangeslinked']['id'] = 't-recentchangeslinked';
        }
        if ( isset( $this->data['nav_urls']['print'] ) && $this->data['nav_urls']['print'] ) {
            $toolbox['print'] = $this->data['nav_urls']['print'];
            $toolbox['print']['id'] = 't-print';
            $toolbox['print']['rel'] = 'alternate';
            $toolbox['print']['msg'] = 'printableversion';
        }
        if ( isset( $this->data['nav_urls']['permalink'] ) && $this->data['nav_urls']['permalink'] ) {
            $toolbox['permalink'] = $this->data['nav_urls']['permalink'];
            if ( $toolbox['permalink']['href'] === '' ) {
                unset( $toolbox['permalink']['href'] );
                $toolbox['ispermalink']['tooltiponly'] = true;
                $toolbox['ispermalink']['id'] = 't-ispermalink';
                $toolbox['ispermalink']['msg'] = 'permalink';
            } else {
                $toolbox['permalink']['id'] = 't-permalink';
            }
        }
        if ( isset( $this->data['nav_urls']['info'] ) && $this->data['nav_urls']['info'] ) {
            $toolbox['info'] = $this->data['nav_urls']['info'];
            $toolbox['info']['id'] = 't-info';
        }
        if ( isset( $this->data['feeds'] ) && $this->data['feeds'] ) {
            $toolbox['feeds']['id'] = 'feedlinks';
            $toolbox['feeds']['links'] = [];
            foreach ( $this->data['feeds'] as $key => $feed ) {
                $toolbox['feeds']['links'][$key] = $feed;
                $toolbox['feeds']['links'][$key]['id'] = "feed-$key";
                $toolbox['feeds']['links'][$key]['rel'] = 'alternate';
                $toolbox['feeds']['links'][$key]['type'] = "application/{$key}+xml";
                $toolbox['feeds']['links'][$key]['class'] = 'feedlink';
            }
        }
        foreach ( [ 'contributions', 'log', 'blockip', 'emailuser',
            'userrights'] as $special
        ) {
            if ( isset( $this->data['nav_urls'][$special] ) && $this->data['nav_urls'][$special] ) {
                $toolbox[$special] = $this->data['nav_urls'][$special];
                $toolbox[$special]['id'] = "t-$special";
            }
        }

        return $toolbox;
    }

    function getShareToolbox() {
        $toolbox = [];
        global $wgServer; //$wgServer = 'https://femiwiki.com';
        $canonicalLink = $wgServer.'/w/'.str_replace('%2F','/',urlencode($this->get('titleprefixeddbkey'))).'?utm_campaign=share';

        $toolbox['copy'] = [];
        $toolbox['copy']['id'] = 'share-copy';
        $toolbox['copy']['href'] = self::shortenURL($canonicalLink);
        $toolbox['copy']['text'] = 'URL 복사';

        $toolbox['facebook'] = [];
        $toolbox['facebook']['id'] = 'share-facebook';
        $toolbox['facebook']['target'] = '_blank';
        $link = $this->shortenURL($canonicalLink.'&utm_source=facebook&utm_medium=post');
        $toolbox['facebook']['href'] = $link;
        $toolbox['facebook']['text'] = '페이스북';

        $toolbox['twitter'] = [];
        $toolbox['twitter']['id'] = 'share-twitter';
        $toolbox['twitter']['target'] = '_blank';
        $link = $this->shortenURL($canonicalLink.'&utm_source=twitter&utm_medium=tweet');
        $tweet = $this->get('title').' '.$link.' #'.$this->get('sitename');
        $toolbox['twitter']['href'] = 'https://twitter.com/intent/tweet?text='.urlencode($tweet);
        $toolbox['twitter']['text'] = '트위터';

        return $toolbox;
    }

    static function shortenURL($longURL){
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL,'https://www.googleapis.com/urlshortener/v1/url'.'?key='.self::$googleApiKey);
        curl_setopt($ch, CURLOPT_POST,1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode(array("longUrl"=>$longURL)));
        curl_setopt($ch, CURLOPT_HTTPHEADER,array("Content-Type: application/json"));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        curl_close($ch);
        if($result !== null && !isset(json_decode($result)->{'id'})) return $longURL;
        return json_decode($result)->{'id'};
    }

    /**
     * Generates user tools menu
     * @return string html
     */
    private function getUserLinks()
    {
        return $this->getPortlet(array(
            'id' => 'p-personal',
            'headerMessage' => 'personaltools',
            'content' => $this->getPersonalTools(),
        ));
    }

    /**
     * Outputs a css clear using the core visualClear class
     */
    private function clear()
    {
        echo '<div class="visualClear"></div>';
    }

      /**
   * Render a series of portals
   *
   * @param array $portals
   */
  protected function renderPortals( $portals ) {
    // Render portals
    foreach ( $portals as $name => $content ) {
        if ( $content === false ) {
            continue;
        }

        // Numeric strings gets an integer when set as key, cast back - T73639
        $name = (string)$name;

        $this->renderPortal( $name, $content );
    }
  }

    /**
     * @param string $name
     * @param array $content
     * @param null|string $msg
     * @param null|string|array $hook
     */
    protected function renderPortal($name, $content, $msg = null, $hook = null)
    {
        if ($msg === null) {
            $msg = $name;
        }
        $msgObj = wfMessage($msg);
        $labelId = Sanitizer::escapeId("p-$name-label");
        ?><div class="portal" role="navigation" id='<?php
        echo Sanitizer::escapeId("p-$name")
        ?>'<?php
        echo Linker::tooltip('p-' . $name)
        ?> aria-labelledby='<?php echo $labelId ?>'>
            <h3<?php $this->html('userlangattributes') ?> id='<?php echo $labelId ?>'><?php
                echo htmlspecialchars($msgObj->exists() ? $msgObj->text() : $msg);
                ?></h3>

            <div class="body">
                <?php
                if (is_array($content)) {
                    ?>
                    <ul>
                        <?php
                        foreach ($content as $key => $val) {
                            echo $this->makeListItem($key, $val);
                        }
                        if ($hook !== null) {
                            Hooks::run($hook, [&$this, true]);
                        }
                        ?>
                    </ul>
                    <?php
                } else {
                    echo $content; /* Allow raw HTML block to be defined by extensions */
                }

                $this->renderAfterPortlet($name);
                ?>
            </div>
        </div><?php
    }
}
