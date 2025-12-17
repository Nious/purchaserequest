<div class="input-group d-flex justify-content-center">
    <input
        wire:model.defer="unit_price.{{ $cart_item->id }}"
        type="number"
        min="0"
        style="min-width: 40px;max-width: 90px;"
        class="form-control"
    >
    <div class="input-group-append">
        <button
            type="button"
            wire:click="updatePrice('{{ $cart_item->rowId }}', {{ $cart_item->id }})"
            class="btn btn-info"
        >
            <i class="bi bi-check"></i>
        </button>
    </div>
</div>
