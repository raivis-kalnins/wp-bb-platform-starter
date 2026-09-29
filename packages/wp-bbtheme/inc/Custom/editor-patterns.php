<?php
/** Block-editor productivity toolbar and custom parent/child pattern library. */
defined( 'ABSPATH' ) || exit;

function wp_theme_editor_productivity_assets() {
    $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
    if ( ! $screen || ! method_exists( $screen, 'is_block_editor' ) || ! $screen->is_block_editor() ) return;
    $post_type = sanitize_key( $screen->post_type ?: 'page' );
    $object = get_post_type_object( $post_type );
    if ( ! $object ) return;

    $handle = 'wp-theme-editor-productivity';
    wp_register_script( $handle, '', array( 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-blocks', 'wp-i18n' ), wp_get_theme()->get( 'Version' ), true );
    wp_localize_script( $handle, 'WPThemeEditorTools', array(
        'newUrl' => admin_url( 'post-new.php?post_type=' . $post_type ),
        'newLabel' => sprintf( __( 'New %s', 'wp-theme' ), $object->labels->singular_name ),
        'patternsLabel' => __( 'Templates', 'wp-theme' ),
        'addSectionLabel' => __( 'Add section', 'wp-theme' ),
        'cloneLabel' => __( 'Clone section', 'wp-theme' ),
        'openPatterns' => ! empty( $_GET['wp_theme_open_patterns'] ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        'dirtyMessage' => __( 'You have unsaved changes. Leave this editor and create new content?', 'wp-theme' ),
        'nothingSelected' => __( 'Select a section or block to clone first.', 'wp-theme' ),
        'libraryTitle' => __( 'Theme Pattern Library', 'wp-theme' ),
        'libraryIntro' => __( 'Insert a reusable section from the WP BBTheme parent or the active child theme.', 'wp-theme' ),
        'searchLabel' => __( 'Search patterns', 'wp-theme' ),
        'insertLabel' => __( 'Insert section', 'wp-theme' ),
        'noPatterns' => __( 'No matching theme patterns found.', 'wp-theme' ),
        'patterns' => function_exists( 'wp_theme_pattern_library_payload' ) ? wp_theme_pattern_library_payload() : array(),
    ) );
    wp_add_inline_script( $handle, <<<'JS'
(function(wp){
  if(!wp||!wp.plugins||!wp.editPost||!wp.element||!wp.components||!wp.data||!wp.blocks)return;
  var el=wp.element.createElement,useState=wp.element.useState,useEffect=wp.element.useEffect;
  var PluginToolbarButton=wp.editPost.PluginToolbarButton||(wp.editor&&wp.editor.PluginToolbarButton);
  var Modal=wp.components.Modal,Button=wp.components.Button,SearchControl=wp.components.SearchControl;
  if(!PluginToolbarButton||!Modal||!Button||!SearchControl)return;
  var cfg=window.WPThemeEditorTools||{},patterns=Array.isArray(cfg.patterns)?cfg.patterns:[];
  function newContent(){var editor=wp.data.select('core/editor');if(editor&&editor.isEditedPostDirty&&editor.isEditedPostDirty()&&!window.confirm(cfg.dirtyMessage))return;window.location.href=cfg.newUrl}
  function deepClone(block){return wp.blocks.createBlock(block.name,Object.assign({},block.attributes),(block.innerBlocks||[]).map(deepClone))}
  function topLevelSelection(){var sel=wp.data.select('core/block-editor'),id=sel&&sel.getSelectedBlockClientId?sel.getSelectedBlockClientId():'';if(!id)return null;var root=sel.getBlockRootClientId(id),top=id;while(root){top=root;root=sel.getBlockRootClientId(top)}return {clientId:top,index:sel.getBlockIndex(top,''),root:''}}
  function cloneSelected(){var sel=wp.data.select('core/block-editor'),dispatch=wp.data.dispatch('core/block-editor'),selected=topLevelSelection();if(!selected){window.alert(cfg.nothingSelected);return}var block=sel.getBlock(selected.clientId);if(!block)return;var clone=deepClone(block);dispatch.insertBlocks(clone,selected.index+1,selected.root);window.setTimeout(function(){dispatch.selectBlock(clone.clientId)},30)}
  function insertPattern(pattern){var blocks=wp.blocks.parse(pattern.content||'');if(!blocks.length)return;var dispatch=wp.data.dispatch('core/block-editor'),selected=topLevelSelection();if(selected)dispatch.insertBlocks(blocks,selected.index+1,selected.root);else dispatch.insertBlocks(blocks)}
  function PatternModal(props){var state=useState(''),query=state[0],setQuery=state[1],needle=(query||'').trim().toLowerCase();var shown=patterns.filter(function(p){return !needle||((p.title||'')+' '+(p.description||'')+' '+(p.source||'')).toLowerCase().indexOf(needle)!==-1});return el(Modal,{title:cfg.libraryTitle,onRequestClose:props.onClose,className:'wp-theme-pattern-modal'},el('div',{className:'wp-theme-pattern-modal__intro'},cfg.libraryIntro),el(SearchControl,{label:cfg.searchLabel,value:query,onChange:setQuery}),shown.length?el('div',{className:'wp-theme-pattern-modal__grid'},shown.map(function(p,i){return el('article',{className:'wp-theme-pattern-modal__card',key:(p.slug||p.title||'pattern')+'-'+i},el('div',{className:'wp-theme-pattern-modal__meta'},el('span',null,p.source||'Theme'),p.slug?el('code',null,p.slug):null),el('h3',null,p.title||'Pattern'),p.description?el('p',null,p.description):null,el(Button,{variant:'primary',onClick:function(){insertPattern(p);props.onClose()}},cfg.insertLabel))})):el('p',{className:'wp-theme-pattern-modal__empty'},cfg.noPatterns))}
  function Tools(){var modalState=useState(!!cfg.openPatterns),open=modalState[0],setOpen=modalState[1];useEffect(function(){if(cfg.openPatterns)setOpen(true)},[]);return el(wp.element.Fragment,null,
    el(PluginToolbarButton,{icon:'plus-alt2',label:cfg.newLabel,onClick:newContent,className:'wp-theme-editor-new'},cfg.newLabel),
    el(PluginToolbarButton,{icon:'layout',label:cfg.patternsLabel,onClick:function(){setOpen(true)},className:'wp-theme-editor-patterns'},cfg.patternsLabel),
    el(PluginToolbarButton,{icon:'insert',label:cfg.addSectionLabel,onClick:function(){setOpen(true)},className:'wp-theme-editor-add-section'},cfg.addSectionLabel),
    el(PluginToolbarButton,{icon:'admin-page',label:cfg.cloneLabel,onClick:cloneSelected,className:'wp-theme-editor-clone'},cfg.cloneLabel),
    open?el(PatternModal,{onClose:function(){setOpen(false)}}):null
  )}
  wp.plugins.registerPlugin('wp-theme-editor-productivity',{render:Tools});
})(window.wp);
JS
    );
    wp_enqueue_script( $handle );
    wp_add_inline_style( 'wp-edit-post', '.wp-theme-editor-new.components-button,.wp-theme-editor-patterns.components-button{background:#3858e9!important;color:#fff!important;border-radius:5px!important;margin-right:4px!important;padding:0 9px!important}.wp-theme-editor-new.components-button:hover,.wp-theme-editor-patterns.components-button:hover{background:#2145e6!important;color:#fff!important}.wp-theme-editor-add-section.components-button,.wp-theme-editor-clone.components-button{border:1px solid #c3c4c7!important;border-radius:5px!important;margin-left:2px!important;padding:0 9px!important}.wp-theme-pattern-modal{width:min(1100px,calc(100vw - 48px))!important;max-width:1100px!important}.wp-theme-pattern-modal__intro{margin:-4px 0 18px;color:#50575e}.wp-theme-pattern-modal__grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin-top:18px}.wp-theme-pattern-modal__card{display:flex;min-height:210px;flex-direction:column;padding:18px;border:1px solid #dcdcde;border-radius:12px;background:#fff}.wp-theme-pattern-modal__card h3{margin:14px 0 8px;font-size:17px}.wp-theme-pattern-modal__card p{margin:0 0 18px;color:#50575e;line-height:1.5}.wp-theme-pattern-modal__card .components-button{align-self:flex-start;margin-top:auto}.wp-theme-pattern-modal__meta{display:flex;align-items:center;justify-content:space-between;gap:8px;font-size:11px;color:#646970}.wp-theme-pattern-modal__meta span{padding:4px 7px;border-radius:999px;background:#f0f0f1;font-weight:700}.wp-theme-pattern-modal__meta code{max-width:55%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.wp-theme-pattern-modal__empty{padding:34px 0;text-align:center;color:#646970}@media(max-width:900px){.wp-theme-pattern-modal__grid{grid-template-columns:1fr 1fr}}@media(max-width:600px){.wp-theme-pattern-modal__grid{grid-template-columns:1fr}}' );
}
add_action( 'enqueue_block_editor_assets', 'wp_theme_editor_productivity_assets', 30 );
