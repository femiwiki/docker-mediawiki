<?php

/**
 * BaseTemplate class for the Femiwiki skin
 *
 * @ingroup Skins
 */
class FemiwikiTemplate extends BaseTemplate
{
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
                        <li class="changes"><a href="/w/페미위키:바뀐글" title="바뀐글"><span class="text">바뀐글</span></a></li>
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

                // Page editing and tools
                echo $this->getPageLinks();

                // Toolbox
                echo $this->renderPortal('tb', $this->getToolbox(), 'toolbox', 'SkinTemplateToolboxEnd');
                ?>
            </div>

            <div id="content" class="mw-body" role="main">
                <?php
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
                echo $this->getIndicators();
                echo Html::rawElement(
                    'h1',
                    array(
                        'class' => 'firstHeading',
                        'lang' => $this->get('pageLanguage')
                    ),
                    $this->get('title')
                );

                echo Html::rawElement(
                    'div',
                    array('id' => 'siteSub'),
                    $this->getMsg('tagline')->parse()
                );
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
        $html .= $this->makeSearchButton('go', array('id' => 'searchGoButton', 'class' => 'searchButton'));
        $html .= Html::closeElement('form');

        return $html;
    }

    /**
     * Generates page-related tools/links
     * @return string html
     */
    private function getPageLinks()
    {
        $html = $this->getPortlet(array(
            'id' => 'p-namespaces',
            'headerMessage' => 'namespaces',
            'content' => $this->data['content_navigation']['namespaces'],
        ));
        $html .= $this->getPortlet(array(
            'id' => 'p-variants',
            'headerMessage' => 'variants',
            'content' => $this->data['content_navigation']['variants'],
        ));
        $html .= $this->getPortlet(array(
            'id' => 'p-views',
            'headerMessage' => 'views',
            'content' => $this->data['content_navigation']['views'],
        ));
        $html .= $this->getPortlet(array(
            'id' => 'p-actions',
            'headerMessage' => 'actions',
            'content' => $this->data['content_navigation']['actions'],
        ));

        return $html;
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
        ?>
        <div class="portal" role="navigation" id='<?php
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
        </div>
        <?php
    }
}
