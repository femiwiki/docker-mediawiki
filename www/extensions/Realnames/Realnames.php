<?php 

/*
Copyright 2011 Olivier Finlay Beaton. All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are
permitted provided that the following conditions are met:

   1. Redistributions of source code must retain the above copyright notice, this list of
      conditions and the following disclaimer.

   2. Redistributions in binary form must reproduce the above copyright notice, this list
      of conditions and the following disclaimer in the documentation and/or other materials
      provided with the distribution.

THIS SOFTWARE IS PROVIDED BY Olivier Finlay Beaton ''AS IS'' AND ANY EXPRESS OR IMPLIED
WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND
FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL Olivier Finlay Beaton OR
CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF
ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

/**
 * Extension to display a user's real name wherever and whenever possible.
 * @file
 * @ingroup Extensions
 * @version 0.3.1
 * @authors Olivier Finlay Beaton (olivierbeaton.com)  
 * @copyright BSD-2-Clause http://www.opensource.org/licenses/BSD-2-Clause  
 * @note this extension is pay-what-you-want, please consider a purchase at http://olivierbeaton.com/
 * @since 2011-09-15, 0.1
 * @note requires MediaWiki 1.7.0   
 * @note coding convention followed: http://www.mediawiki.org/wiki/Manual:Coding_conventions
 */
 
if ( !defined( 'MEDIAWIKI' ) ) {
        die( 'This file is a MediaWiki extension, it is not a valid entry point' );
}

/* (not our var to doc)
 * extension credits
 * @since 2011-09-16, 0.1
 */
$wgExtensionCredits['parserhook'][] = array(
  'name' => 'Realnames',
  'author' =>array('[http://olivierbeaton.com/ Olivier Finlay Beaton]'), 
  'version' => '0.3.1',
  'url' => 'http://www.mediawiki.org/wiki/Extension:Realnames', 
  'description' => 'Displays a user\'s real name everywhere',
 );

/**
 * The format to apply to a user link.
 * @since 2011-09-15, 0.1
 * @see $wgRealnamesFormats 
 */ 
$wgRealnamesLinkStyle = 'replace';

/**
 * The format to apply to a user's name in text. 
 * This typically only replaces User: text in titles
 * @since 2011-09-16, 0.1
 */  
$wgRealnamesBareStyle = false;

/**
 * Do you want to show blank real names?
 * If this is false, then it will fall back on a 'replace' username style.
 * If true, then in a style like 'append' ( Joe [Joe Cardigan] )you will see: Joe []  
 * @note User:Joe text will still become Joe.
 * @since 2011-09-15, 0.1
 */    
$wgRealnamesBlank = false;

/**
 * Ability to turn on/off replacement in each area.
 * This runs a bit counter to the idea of the extension, to simply replace all
 * names on the page, however baring better names handling sometimes turning off
 * say (titles) is the only way to go and I don't want people to have to fork/patch
 * the code to do so.
 * @attention use of opt-outs here is discouraged.
 * @since 2011-11-05, 0.1
 */
$wgRealnamesReplacements = array(
    'title' => TRUE,
    'subtitle' => TRUE,
    'personnal' => TRUE,
    'body' => TRUE,
  );

/**
 * Possible styles to pick from, you can define new ones as well.
 * The following variables are set:<br> 
 * \li $1  link start
 * \li $2  username
 * \li $3  real name
 * \li $4  link end
 * @note If you want to add markup, you should set $wgRealnamesBareStyle to a style without html (it's doesnt work in bare)
 * @since 2011-09-15, 0.1  
 */
$wgRealnamesStyles = array( 
    'standard' => '$1$2$4',
    'append' => '$1$2$4 [$3]',
    'replace' => '$1$3$4',
    'reverse' => '$1$3$4 [$2]',
    'dash' => '$1$2$4 &ndash; $3',
    'femiwiki' => '$3 [$1$2$4]',
  ); 
  
/**
 * Allows you to turn off smart behaviour.
 * Set the var to FALSE to disable all,
 * or turn off individual features.
 */
$wgRealnamesSmart = array(
    'same' => TRUE,
  );
  
/**
 * extra namespaces names to look for.
 * @note do not include the ':'
 * @note this is a regexp so escaping may be required. 
 * @since 2011-09-22, 0.2
 */ 
$wgRealnamesNamespaces = array();
 
if (isset($wgConfigureAdditionalExtensions) && is_array($wgConfigureAdditionalExtensions)) {

  /* (not our var to doc)
   * attempt to tell Extension:Configure how to web configure our extension
   * @since 2011-09-22, 0.2 
   */ 
  $wgConfigureAdditionalExtensions[] = array(
      'name' => 'Realnames',
      'settings' => array(
          'wgRealnamesLinkStyle' => 'text',
          'wgRealnamesBareStyle' => 'bool',
          'wgRealnamesBlank' => 'bool',
          'wgRealnamesStyles' => 'array',   
          'wgRealnamesSmart' => 'array',
          'wgRealnamesReplacements' => 'array',
          'wgRealnamesNamespaces' => 'array',     
        ),
      'array' => array(
          'wgRealnamesStyles' => 'assoc',
          'wgRealnamesSmart' => 'assoc',
          'wgRealnamesReplacements' => 'assoc',
          'wgRealnamesNamespaces' => 'simple',
        ),
      'schema' => false,
      'url' => 'http://www.mediawiki.org/wiki/Extension:Realnames',
    );
   
} // $wgConfigureAdditionalExtensions exists
   
 
/* (not our var to doc)
 * Our extension class, it will load the first time the core tries to access it
 * @since 2011-09-16, 0.1  
 */ 
$wgAutoloadClasses['ExtRealnames'] = dirname(__FILE__) . '/Realnames.body.php';
$wgMessagesDirs['ExtRealnames'] = dirname( __FILE__ ) . "/i18n";

/* (not our var to doc)
 * This hook is called before the article is displayed.  
 * @since 2011-09-16, 0.1  
 * @see $wgAutoloadClasses for how the class gets defined.  
 */
$wgHooks['BeforePageDisplay'][] = 'ExtRealnames::hookBeforePageDisplay';

//
$wgHooks['GetLogTypesOnUser'][] = 'ExtRealnames::onGetLogTypesOnUser';
$wgHooks['UserLoadOptions'][] = 'ExtRealnames::onUserLoadOptions';
$wgHooks['UserSaveSettings'][] = 'ExtRealnames::onUserSaveSettings';

$wgLogTypes[] = 'nickname';
$wgLogActionsHandlers['nickname/nickname'] = 'LogFormatter';

/* (not our var to doc)
 * This hook is called before the user links are displayed.  
 * @since 2011-09-22, 0.2  
 * @see $wgAutoloadClasses for how the class gets defined.  
 */
$wgHooks['PersonalUrls'][] = 'ExtRealnames::hookPersonalUrls';
