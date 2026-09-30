<script>
  import { onMount } from 'svelte';

  export let restUrl = '';
  export let nonce = '';

  let status = null;
  let error = '';

  onMount(async () => {
    try {
      const response = await fetch(restUrl, {
        credentials: 'same-origin',
        headers: { 'X-WP-Nonce': nonce }
      });
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      status = await response.json();
    } catch (e) {
      error = e.message;
    }
  });
</script>

<section class="wpbb-svelte-status" aria-live="polite">
  {#if error}
    <p>Status request failed: {error}</p>
  {:else if !status}
    <p>Loading catalogue status…</p>
  {:else}
    <div class="metrics">
      <article><strong>{status.products}</strong><span>Products</span></article>
      <article><strong>{status.demoProducts}</strong><span>Demo products</span></article>
      <article><strong>{status.objectCache ? 'ON' : 'OFF'}</strong><span>Object cache</span></article>
      <article><strong>{status.hpos ? 'ON' : 'OFF'}</strong><span>Woo HPOS</span></article>
    </div>
  {/if}
</section>

<style>
  .metrics{display:grid;grid-template-columns:repeat(4,1fr);gap:.75rem}
  article{padding:1rem;border:1px solid #dce7df;border-radius:.75rem;background:white}
  strong,span{display:block}
  strong{font-size:1.4rem;color:#0d432f}
  span{font-size:.75rem;color:#607168}
</style>
