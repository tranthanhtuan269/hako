<div class="store-coupons-modal" id="store-coupons-modal" hidden>
    <div class="store-coupons-modal__backdrop" data-store-coupons-close></div>
    <div class="store-coupons-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="store-coupons-title">
        <div class="store-coupons-modal__header">
            <div>
                <h3 id="store-coupons-title">Store coupons</h3>
                <p class="form-hint" id="store-coupons-subtitle" style="margin:.25rem 0 0;">Drag to reorder. Edit fields and click Save on each row.</p>
            </div>
            <button type="button" class="store-coupons-modal__close" data-store-coupons-close aria-label="Close">&times;</button>
        </div>
        <div class="store-coupons-modal__body">
            <p class="coupon-sort-status" id="store-coupons-status" aria-live="polite" hidden></p>
            <div class="store-coupons-modal__loading" id="store-coupons-loading">Loading coupons…</div>
            <div class="store-coupons-modal__empty" id="store-coupons-empty" hidden>No coupons for this store yet.</div>
            <div class="store-coupons-table-wrap" id="store-coupons-table-wrap" hidden>
                <table class="store-coupons-table coupon-sort-table">
                    <thead>
                        <tr>
                            <th class="coupon-sort-col" aria-label="Reorder"></th>
                            <th>Order</th>
                            <th>Title / Description</th>
                            <th>Code</th>
                            <th>Expires</th>
                            <th>Flags</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="store-coupons-sortable"></tbody>
                </table>
            </div>
        </div>
        <div class="store-coupons-modal__footer">
            <a href="#" class="btn btn-outline" id="store-coupons-create" target="_blank" rel="noopener">+ Add coupon</a>
            <button type="button" class="btn btn-primary" data-store-coupons-close>Close</button>
        </div>
    </div>
</div>
