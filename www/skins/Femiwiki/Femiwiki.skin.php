<?php

/**
 * SkinTemplate class for the Femiwiki skin
 *
 * @ingroup Skins
 */
class SkinFemiwiki extends SkinTemplate
{
    public $skinname = 'femiwiki', $stylename = 'Femiwiki',
        $template = 'FemiwikiTemplate', $useHeadElement = true;

    /**
     * Add CSS via ResourceLoader
     *
     * @param $out OutputPage
     */
    public function initPage(OutputPage $out)
    {

        $out->addMeta('viewport', 'width=device-width, initial-scale=1.0');

        // Twitter card
        $out->addMeta('twitter:card', 'summary');
        $out->addMeta('twitter:site', '@femiwikidotcome');

        // Favicons
        $out->addHeadItem('fav0', "<link rel='apple-touch-icon' sizes='57x75' href='/fw-resources/favicons/apple-icon-57x57.png'>");
        $out->addHeadItem("fav1", "<link rel='apple-touch-icon' sizes='57x75' href='/fw-resources/favicons/apple-icon-57x57.png'>");
        $out->addHeadItem("fav2", "<link rel='apple-touch-icon' sizes='60x60' href='/fw-resources/favicons/apple-icon-60x60.png'>");
        $out->addHeadItem("fav3", "<link rel='apple-touch-icon' sizes='72x72' href='/fw-resources/favicons/apple-icon-72x72.png'>");
        $out->addHeadItem("fav4", "<link rel='apple-touch-icon' sizes='76x76' href='/fw-resources/favicons/apple-icon-76x76.png'>");
        $out->addHeadItem("fav5", "<link rel='apple-touch-icon' sizes='114x114' href='/fw-resources/favicons/apple-icon-114x114.png'>");
        $out->addHeadItem("fav6", "<link rel='apple-touch-icon' sizes='120x120' href='/fw-resources/favicons/apple-icon-120x120.png'>");
        $out->addHeadItem("fav7", "<link rel='apple-touch-icon' sizes='144x144' href='/fw-resources/favicons/apple-icon-144x144.png'>");
        $out->addHeadItem("fav8", "<link rel='apple-touch-icon' sizes='152x152' href='/fw-resources/favicons/apple-icon-152x152.png'>");
        $out->addHeadItem("fav9", "<link rel='apple-touch-icon' sizes='180x180' href='/fw-resources/favicons/apple-icon-180x180.png'>");
        $out->addHeadItem("fav10", "<link rel='icon' type='image/png' sizes='192x192'  href='/fw-resources/favicons/android-icon-192x192.png'>");
        $out->addHeadItem("fav11", "<link rel='icon' type='image/png' sizes='32x32' href='/fw-resources/favicons/favicon-32x32.png'>");
        $out->addHeadItem("fav12", "<link rel='icon' type='image/png' sizes='96x96' href='/fw-resources/favicons/favicon-96x96.png'>");
        $out->addHeadItem("fav13", "<link rel='icon' type='image/png' sizes='16x16' href='/fw-resources/favicons/favicon-16x16.png'>");
        $out->addHeadItem("fav14", "<link rel='manifest' href='/fw-resources/favicons/manifest.json'>");
        $out->addHeadItem("fav15", "<meta name='msapplication-TileColor' content='#ffffff'>");
        $out->addHeadItem("fav16", "<meta name='msapplication-TileImage' content='/fw-resources/favicons/ms-icon-144x144.png'>");
        $out->addHeadItem("fav17", "<meta name='theme-color' content='#ffffff'>");

        $out->addModuleStyles(array(
            'mediawiki.skinning.interface',
            'mediawiki.skinning.content.externallinks',
            'skins.femiwiki'
        ));
        $out->addModules(array(
            'skins.femiwiki.js'
        ));
    }

    /**
     * @param $out OutputPage
     */
    function setupSkinUserCss(OutputPage $out)
    {
        parent::setupSkinUserCss($out);
    }
}
