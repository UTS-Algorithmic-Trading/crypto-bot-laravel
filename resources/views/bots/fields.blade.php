<div class="mb-3">
    <label for="name" class="form-label">Name</label>
    <input type="text" name="name" class="form-control" id="name" value="{{ old('name') ?? (isset($bot) ? $bot->name : '') }}" placeholder="Name">
  </div>

  <div class="mb-3">
    <label for="initial_bet" class="form-label">Initial Trade</label>
    <input type="text" name="initial_bet" class="form-control" id="initial_bet" value="{{ old('initial_bet') ?? (isset($bot) ? $bot->initial_bet : '') }}" placeholder="100.00">
  </div>

  <div class="mb-3">
    <label for="max_bet" class="form-label">Max Trade</label>
    <input type="text" name="max_bet" class="form-control" id="max_bet" value="{{ old('max_bet') ?? (isset($bot) ? $bot->max_bet : '') }}" placeholder="1000.00">
  </div>

  <div class="mb-3">
    <label for="stock_type" class="form-label">Crypto Currency</label>
    <input type="text" name="stock_type" class="form-control" id="stock_type" value="{{ old('stock_type') ?? (isset($bot) ? $bot->stock_type : '') }}" placeholder="BTC">
  </div>

  <div class="mb-3">
    <label for="target_profit_percent" class="form-label">Target Profit (%)</label>
    <input type="text" name="target_profit_percent" class="form-control" id="target_profit_percent" value="{{ old('target_profit_percent') ?? (isset($bot) ? $bot->target_profit_percent : '') }}" placeholder="200.00">
  </div>

  <div class="mb-3">
    <label for="trailing_profit_percent" class="form-label">Trailing Profit (%)</label>
    <input type="text" name="trailing_profit_percent" class="form-control" id="trailing_profit_percent" value="{{ old('trailing_profit_percent') ?? (isset($bot) ? $bot->trailing_profit_percent : '') }}" placeholder="10.00">
  </div>

  <div class="mb-3">
    <label for="stop_loss_percent" class="form-label">Stop Loss Profit (%)</label>
    <input type="text" name="stop_loss_percent" class="form-control" id="stop_loss_percent" value="{{ old('stop_loss_percent') ?? (isset($bot) ? $bot->stop_loss_percent : '') }}" placeholder="50.00">
  </div>