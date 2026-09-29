/**
 * BBuilder Front-End Editor — Interactivity API Store
 */
import { store, getContext, getElement } from '@wordpress/interactivity';

store( 'wp-bbuilder-frontend-editor', {
  state: {
    activeBlockIndex: null,
    originalHTML: '',
    originalOuterHTML: '',
    currentAlign: '',
  },

  actions: {
    editBlock( event ) {
      if ( event && event.target && event.target.closest( '.wpbb-fie-inline-toolbar, .wpbb-fie-savebar' ) ) {
        return;
      }

      const ctx = getContext();
      const { state } = store( 'wp-bbuilder-frontend-editor' );
      const el = getElement();
      const inner = wpbbFieFindEditableElement( el.ref );
      if ( ! inner ) return;

      if ( state.isEditing && state.activeBlockIndex === ctx.blockIndex ) {
        inner.focus();
        wpbbFiePositionInlineToolbar();
        wpbbFieUpdateAlignmentButtons( state.currentAlign );
        return;
      }

      if ( state.isEditing && state.activeBlockIndex !== ctx.blockIndex ) {
        return;
      }

      state.activeBlockIndex = ctx.blockIndex;
      state.originalHTML = inner.innerHTML;
      state.originalOuterHTML = inner.outerHTML;
      state.currentAlign = wpbbFieGetAlignment( inner );
      state.isEditing = true;
      state.message = '';

      ctx.active = true;
      inner.contentEditable = 'true';
      inner.classList.add( 'wpbb-fie-editable-element' );
      inner.focus();
      wpbbFieSelectEditable( inner );
      setTimeout( () => {
        wpbbFiePositionInlineToolbar();
        wpbbFieUpdateAlignmentButtons( state.currentAlign );
      }, 0 );
    },

    formatInline( event ) {
      if ( event ) {
        event.preventDefault();
        event.stopPropagation();
      }
      const el = getElement();
      const command = el.ref.dataset.wpbbCommand;
      const activeInner = wpbbFieGetActiveEditable();
      if ( ! command || ! activeInner ) return;

      activeInner.focus();
      document.execCommand( command, false, null );
    },

    setAlignment( event ) {
      if ( event ) {
        event.preventDefault();
        event.stopPropagation();
      }
      const el = getElement();
      const align = el.ref.dataset.wpbbAlign || '';
      const activeInner = wpbbFieGetActiveEditable();
      if ( ! activeInner ) return;

      wpbbFieApplyAlignment( activeInner, align );
      const { state } = store( 'wp-bbuilder-frontend-editor' );
      state.currentAlign = align;
      wpbbFieUpdateAlignmentButtons( align );
      activeInner.focus();
    },

    cancel( event ) {
      if ( event ) {
        event.preventDefault();
        event.stopPropagation();
      }
      const { state } = store( 'wp-bbuilder-frontend-editor' );
      const activeEl = document.querySelector( '.wpbb-fie-active' );
      if ( activeEl ) {
        const inner = activeEl.querySelector( '.wpbb-fie-editable-element, [contenteditable="true"]' );
        if ( inner ) {
          if ( state.originalOuterHTML ) {
            inner.outerHTML = state.originalOuterHTML;
          } else {
            inner.innerHTML = state.originalHTML;
            inner.contentEditable = 'false';
            inner.classList.remove( 'wpbb-fie-editable-element' );
          }
        }
      }
      wpbbFieResetState( state );
      wpbbFieHideInlineToolbar();
    },

    async save( event ) {
      if ( event ) {
        event.preventDefault();
        event.stopPropagation();
      }
      const { state } = store( 'wp-bbuilder-frontend-editor' );
      const activeEl = document.querySelector( '.wpbb-fie-active' );
      if ( ! activeEl ) return;
      const inner = activeEl.querySelector( '.wpbb-fie-editable-element, [contenteditable="true"]' );
      if ( ! inner ) return;

      state.isSaving = true;
      state.message = '';

      try {
        const resp = await fetch( state.endpoint, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': state.restNonce,
          },
          body: JSON.stringify( {
            postId: state.postId,
            blockIndex: state.activeBlockIndex,
            newContent: inner.innerHTML,
            newHTML: wpbbFieGetCleanOuterHTML( inner ),
          } ),
        } );

        if ( ! resp.ok ) {
          const err = await resp.json().catch( () => ( {} ) );
          throw new Error( err.message || 'Save failed' );
        }

        inner.contentEditable = 'false';
        inner.classList.remove( 'wpbb-fie-editable-element' );
        state.message = 'Saved';
        wpbbFieHideInlineToolbar();
        setTimeout( () => { state.message = ''; }, 2000 );
        wpbbFieResetState( state );
      } catch ( err ) {
        state.message = 'Error: ' + err.message;
        state.isSaving = false;
      }
    },
  },

  callbacks: {
    syncEditable() {
      const ctx = getContext();
      const { state } = store( 'wp-bbuilder-frontend-editor' );
      if ( state.activeBlockIndex !== ctx.blockIndex ) {
        ctx.active = false;
      }
    },
  },
} );

function wpbbFieFindEditableElement( blockEl ) {
  if ( ! blockEl ) return null;
  return blockEl.querySelector( 'p, h1, h2, h3, h4, h5, h6, li, blockquote, pre' );
}

function wpbbFieGetActiveEditable() {
  const activeEl = document.querySelector( '.wpbb-fie-active' );
  if ( ! activeEl ) return null;
  return activeEl.querySelector( '.wpbb-fie-editable-element, [contenteditable="true"]' );
}

function wpbbFieGetCleanOuterHTML( editable ) {
  const clone = editable.cloneNode( true );
  clone.removeAttribute( 'contenteditable' );
  clone.classList.remove( 'wpbb-fie-editable-element' );
  if ( clone.getAttribute( 'class' ) === '' ) {
    clone.removeAttribute( 'class' );
  }
  return clone.outerHTML;
}

function wpbbFieSelectEditable( editable ) {
  if ( ! editable || ! window.getSelection || ! document.createRange ) return;
  const range = document.createRange();
  range.selectNodeContents( editable );
  range.collapse( false );
  const selection = window.getSelection();
  selection.removeAllRanges();
  selection.addRange( range );
}

function wpbbFieGetAlignment( editable ) {
  if ( ! editable ) return '';
  if ( editable.classList.contains( 'has-text-align-center' ) ) return 'center';
  if ( editable.classList.contains( 'has-text-align-right' ) ) return 'right';
  if ( editable.classList.contains( 'has-text-align-left' ) ) return 'left';
  const inlineAlign = ( editable.style.textAlign || '' ).toLowerCase();
  return [ 'left', 'center', 'right' ].includes( inlineAlign ) ? inlineAlign : '';
}

function wpbbFieApplyAlignment( editable, align ) {
  editable.classList.remove( 'has-text-align-left', 'has-text-align-center', 'has-text-align-right' );
  editable.style.textAlign = '';

  if ( [ 'left', 'center', 'right' ].includes( align ) ) {
    editable.classList.add( 'has-text-align-' + align );
  }
}

function wpbbFieUpdateAlignmentButtons( align ) {
  document.querySelectorAll( '.wpbb-fie-align-btn' ).forEach( ( button ) => {
    const buttonAlign = button.dataset.wpbbAlign || '';
    button.setAttribute( 'aria-pressed', buttonAlign === align ? 'true' : 'false' );
  } );
}

function wpbbFiePositionInlineToolbar() {
  const toolbar = document.querySelector( '.wpbb-fie-inline-toolbar' );
  const activeEl = document.querySelector( '.wpbb-fie-active' );
  if ( ! toolbar || ! activeEl ) return;

  toolbar.classList.add( 'wpbb-fie-inline-toolbar-visible' );
  const rect = activeEl.getBoundingClientRect();
  const width = toolbar.offsetWidth || 360;
  const height = toolbar.offsetHeight || 42;
  const margin = 12;
  const maxLeft = Math.max( margin, window.innerWidth - width - margin );
  const left = Math.min( Math.max( rect.left, margin ), maxLeft );
  let top = rect.top - height - margin;

  if ( top < margin ) {
    top = rect.bottom + margin;
  }

  toolbar.style.left = left + 'px';
  toolbar.style.top = top + 'px';
}

function wpbbFieHideInlineToolbar() {
  const toolbar = document.querySelector( '.wpbb-fie-inline-toolbar' );
  if ( ! toolbar ) return;
  toolbar.classList.remove( 'wpbb-fie-inline-toolbar-visible' );
  toolbar.style.left = '';
  toolbar.style.top = '';
}

function wpbbFieResetState( state ) {
  state.isEditing = false;
  state.isSaving = false;
  state.activeBlockIndex = null;
  state.originalHTML = '';
  state.originalOuterHTML = '';
  state.currentAlign = '';
}

window.addEventListener( 'scroll', wpbbFiePositionInlineToolbar, { passive: true } );
window.addEventListener( 'resize', wpbbFiePositionInlineToolbar );

// Keep the text selection alive while toolbar buttons are clicked.
document.addEventListener( 'mousedown', ( event ) => {
  if ( event.target && event.target.closest( '.wpbb-fie-inline-toolbar' ) ) {
    event.preventDefault();
  }
} );
