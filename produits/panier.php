<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

require_login();

// Détails du panier
$details = cart_details($pdo); // ['items'=>[], 'total_ht'=>float]
$subtotal_ht = (float)$details['total_ht'];
$tva      = round($subtotal_ht * TVA_RATE, 2);
$shipping = ($subtotal_ht >= FREE_SHIPPING_THRESHOLD || $subtotal_ht <= 0) ? 0.0 : (float)SHIPPING_FLAT;
$total_ttc = round($subtotal_ht + $tva + $shipping, 2);

// token CSRF pour formulaires et AJAX
$csrf = csrf_token();

include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h3 mb-0">Votre panier</h1>
  <a class="btn btn-light" href="<?= BASE_URL ?>/produits/produit_index.php">Continuer mes achats</a>
</div>

<?php if (!$details['items']): ?>
  <div class="alert alert-info">Votre panier est vide.</div>
<?php else: ?>

<div class="table-responsive">
  <table class="table align-middle">
    <thead>
      <tr>
        <th>Produit</th>
        <th>Désignation</th>
        <th class="text-end">Prix unitaire</th>
        <th class="text-center" style="width:140px;">Quantité</th>
        <th class="text-end">Sous-total</th>
        <th></th>
      </tr>
    </thead>
    <tbody id="cart-body">
      <?php foreach ($details['items'] as $it): ?>
        <tr data-id="<?= (int)$it['id_p'] ?>">
          <td style="width:80px">
            <img src="<?= htmlspecialchars($it['image_url']) ?>" alt=""
                 style="width:64px;height:64px;object-fit:cover;border-radius:8px;">
          </td>
          <td>
            <div class="fw-semibold"><?= htmlspecialchars($it['designation_p']) ?></div>
            <div class="text-muted small"><?= htmlspecialchars($it['type_p']) ?></div>
            <?php if (!empty($it['ppromo']) && (float)$it['ppromo'] > 0): ?>
              <span class="badge text-bg-danger mt-1">-<?= (float)$it['ppromo'] ?>%</span>
            <?php endif; ?>
          </td>
          <td class="text-end">
            <?php if (!empty($it['ppromo']) && (float)$it['ppromo'] > 0): ?>
              <span class="text-decoration-line-through text-muted me-1"><?= fmt_eur($it['prix_ht']) ?></span>
              <strong><?= fmt_eur($it['unit_final']) ?></strong>
            <?php else: ?>
              <strong><?= fmt_eur($it['unit_final']) ?></strong>
            <?php endif; ?>
          </td>
          <td class="text-center">
            <input type="number"
                   class="form-control form-control-sm js-qty"
                   value="<?= (int)$it['qty'] ?>"
                   min="0"
                   max="<?= (int)$it['stock_p'] ?>"
                   data-id="<?= (int)$it['id_p'] ?>"
                   style="width:90px;display:inline-block;text-align:center;">
          </td>
          <td class="text-end">
            <strong class="js-line-total"><?= fmt_eur($it['line_total']) ?></strong>
          </td>
          <td class="text-end">
            <form method="post" class="d-inline js-remove-form">
              <input type="hidden" name="csrf" value="<?= $csrf ?>">
              <input type="hidden" name="id" value="<?= (int)$it['id_p'] ?>">
              <button class="btn btn-sm btn-outline-danger js-remove">Supprimer</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="row justify-content-end">
  <div class="col-md-5">
    <div class="card p-3">
      <div class="d-flex justify-content-between">
        <span>Sous-total (HT)</span>
        <strong id="subtotal-ht"><?= fmt_eur($subtotal_ht) ?></strong>
      </div>
      <div class="d-flex justify-content-between">
        <span>TVA (<?= (int)(TVA_RATE*100) ?>%)</span>
        <strong id="tva"><?= fmt_eur($tva) ?></strong>
      </div>
      <div class="d-flex justify-content-between">
        <span>Livraison</span>
        <strong id="shipping"><?= $shipping > 0 ? fmt_eur($shipping) : 'Offerte' ?></strong>
      </div>
      <hr class="my-2">
      <div class="d-flex justify-content-between fs-5">
        <span>Total TTC</span>
        <strong id="total-ttc"><?= fmt_eur($total_ttc) ?></strong>
      </div>
      <?php if ($subtotal_ht > 0 && $subtotal_ht < FREE_SHIPPING_THRESHOLD): ?>
        <div class="text-muted small mt-2">
          Plus que <?= fmt_eur(FREE_SHIPPING_THRESHOLD - $subtotal_ht) ?> pour la livraison offerte.
        </div>
      <?php endif; ?>
      <div class="d-grid mt-3">
        <button class="btn btn-primary" disabled>Commander (demo)</button>
      </div>
    </div>
  </div>
</div>

<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<?php if ($details['items']): ?>
<script>
(function(){
  const csrf = <?= json_encode($csrf) ?>;

  // Utilitaires
  function eur(n){ return new Intl.NumberFormat('fr-FR',{style:'currency',currency:'EUR'}).format(Number(n||0)); }

  async function postForm(url, data){
    const body = new URLSearchParams(data);
    const res = await fetch(url, {
      method: 'POST',
      headers: {'Content-Type':'application/x-www-form-urlencoded'},
      body
    });
    return res.json();
  }

  function updateTotals(t){
    document.getElementById('subtotal-ht').textContent = eur(t.subtotal_ht);
    document.getElementById('tva').textContent         = eur(t.tva);
    document.getElementById('shipping').textContent    = t.shipping>0 ? eur(t.shipping) : 'Offerte';
    document.getElementById('total-ttc').textContent   = eur(t.total_ttc);

    // Si tu as un compteur dans le header avec id="cart-count", on le met à jour
    const span = document.getElementById('cart-count');
    if (span) span.textContent = t.count;
  }

  // Changement de quantité
  document.querySelectorAll('.js-qty').forEach(inp=>{
    inp.addEventListener('change', async (e)=>{
      const id  = inp.dataset.id;
      let qty   = parseInt(inp.value, 10);
      if (isNaN(qty) || qty < 0) qty = 0;

      const data = { csrf, action:'set', id, qty };
      const json = await postForm('<?= BASE_URL ?>/produits/panier_api.php', data);
      if (!json.ok) { alert(json.error||'Erreur'); return; }

      // si la ligne n'existe plus (qty=0), retire la <tr>
      const tr = inp.closest('tr');
      if (!json.line) {
        tr?.parentNode?.removeChild(tr);
      } else {
        // mettre à jour quantité (éventuellement corrigée par le serveur) et sous-total ligne
        inp.value = json.line.qty;
        tr.querySelector('.js-line-total').textContent = eur(json.line.line_total);
      }

      updateTotals(json.totals);

      // si plus aucune ligne -> recharger la page pour afficher "panier vide"
      if (json.totals.count === 0) location.reload();
    });
  });

  // Supprimer une ligne (sans rechargement)
  document.querySelectorAll('.js-remove-form').forEach(form=>{
    form.addEventListener('submit', async (e)=>{
      e.preventDefault();
      const id = form.querySelector('input[name="id"]').value;
      const json = await postForm('<?= BASE_URL ?>/produits/panier_api.php', {csrf, action:'remove', id});
      if (!json.ok) { alert(json.error||'Erreur'); return; }

      // retirer la ligne
      const tr = document.querySelector('tr[data-id="'+id+'"]');
      tr?.parentNode?.removeChild(tr);

      updateTotals(json.totals);
      if (json.totals.count === 0) location.reload();
    });
  });

})();
</script>
<?php endif; ?>
