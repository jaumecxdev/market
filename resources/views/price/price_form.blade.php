<section class="content">
    <div class="row">
        <div class="col-12">

            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Cálculo del precio en Marketplaces</h3>
                </div>

                <div class="card-body">

                    <div class="row">
                        <div class="col-sm-3">
                            <div class="form-group">
                                <label for="cost">Coste € (sin iva)</label>
                                <input id="cost" name="cost" type="text" class="form-control cost">
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="form-group">
                                <label for="canon">Canon €</label>
                                <input id="canon" name="canon" type="text" class="form-control canon">
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="form-group">
                                <label for="rappel">Rappel %</label>
                                <input id="rappel" name="rappel" type="text" class="form-control rappel">
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="form-group">
                                <label for="ports">Portes € (con iva)</label>
                                <input id="ports" name="ports" type="text" class="form-control ports">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-3">
                            <div class="alert alert-warning">
                                <h5><i class="icon fas fa-euro-sign"></i> Coste total</h5>
                                <span id="total_cost"></span>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-3">
                            <div class="form-group">
                                <label for="bfit_min">Margen Bcio mínimo €</label>
                                <input id="bfit_min" name="bfit_min" type="text" class="form-control bfit_min">
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="form-group">
                                <label for="mp_fee">Comisión del Marketplace %</label>
                                <input id="mp_fee" name="mp_fee" type="text" class="form-control mp_fee">
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="form-group">
                                <label for="mp_fee_addon">Comisión del Marketplace €</label>
                                <input id="mp_fee_addon" name="mp_fee_addon" type="text" class="form-control mp_fee_addon">
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="form-group">
                                <label for="iva">IVA %</label>
                                <input id="iva" name="iva" type="text" class="form-control iva">
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</section>
