@extends('layouts.app')

@section('title', 'Edit Product')

@section('breadcrumb')
    <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Products</a></li>
        <li class="breadcrumb-item active">Edit</li>
    </ol>
@endsection

@section('content')
    <div class="container-fluid">
        <form id="product-form" action="{{ route('products.update', $product->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PATCH')
            <div class="row">
                <div class="col-lg-12">
                    @include('utils.alerts')
                    <div class="form-group">
                        <button class="btn btn-primary">Update Product <i class="bi bi-check"></i></button>
                    </div>
                </div>

                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            {{-- Product Name & Code --}}
                            <div class="form-row">
                                <div class="col-md-6">
                                    <label for="product_name">Product Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="product_name" required value="{{ old('product_name', $product->product_name) }}">
                                </div>
                                <div class="col-md-6">
                                    <label for="product_code">Code <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="product_code" required value="{{ old('product_code', $product->product_code) }}">
                                </div>
                            </div>

                            {{-- Category & Barcode --}}
                            <div class="form-row">
                                <div class="col-md-6">
                                    <label for="category_id">Category <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <select class="form-control" name="category_id" id="category_id" required>
                                            <option value="" disabled>Select Category</option>
                                            @foreach(\Modules\Product\Entities\Category::all() as $category)
                                                <option value="{{ $category->id }}" {{ $category->id == $product->category_id ? 'selected' : '' }}>
                                                    {{ $category->category_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="input-group-append">
                                            <button data-toggle="modal" data-target="#categoryCreateModal" class="btn btn-outline-primary" type="button">
                                                Add
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="barcode_symbology">Barcode Symbology <span class="text-danger">*</span></label>
                                    <select class="form-control" name="product_barcode_symbology" id="barcode_symbology" required>
                                        <option value="" disabled>Select Symbology</option>
                                        <option value="C128" {{ $product->product_barcode_symbology == 'C128' ? 'selected' : '' }}>Code 128</option>
                                        <option value="C39" {{ $product->product_barcode_symbology == 'C39' ? 'selected' : '' }}>Code 39</option>
                                        <option value="UPCA" {{ $product->product_barcode_symbology == 'UPCA' ? 'selected' : '' }}>UPC-A</option>
                                        <option value="UPCE" {{ $product->product_barcode_symbology == 'UPCE' ? 'selected' : '' }}>UPC-E</option>
                                        <option value="EAN13" {{ $product->product_barcode_symbology == 'EAN13' ? 'selected' : '' }}>EAN-13</option>
                                        <option value="EAN8" {{ $product->product_barcode_symbology == 'EAN8' ? 'selected' : '' }}>EAN-8</option>
                                    </select>
                                </div>
                            </div>

                            {{-- Price & Unit --}}
                            <div class="form-row">
                                <div class="col-md-6">
                                    <label for="product_price">Price <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">Rp</span>
                                        </div>
                                        <input id="product_price" type="text" class="form-control" name="product_price" required value="{{ old('product_price', $product->product_price) }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="product_unit">Unit <span class="text-danger">*</span></label>
                                    <select class="form-control" name="product_unit" id="product_unit" required>
                                        <option value="" disabled>Select Unit</option>
                                        @foreach(\Modules\Setting\Entities\Unit::all() as $unit)
                                            <option value="{{ $unit->short_name }}" {{ $unit->short_name == $product->product_unit ? 'selected' : '' }}>
                                                {{ $unit->name . ' | ' . $unit->short_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            {{-- Quantity & Stock Alert
                            <div class="form-row">
                                <div class="col-md-6">
                                    <label for="product_quantity">Quantity <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" name="product_quantity" min="1" required value="{{ old('product_quantity', $product->product_quantity) }}">
                                </div>
                                <div class="col-md-6">
                                    <label for="product_stock_alert">Alert Quantity <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" name="product_stock_alert" min="0" required value="{{ old('product_stock_alert', $product->product_stock_alert) }}">
                                </div>
                            </div> --}}

                            {{-- Tax --}}
                            <div class="form-row">
                                <div class="col-md-6">
                                    <label for="product_order_tax">Tax (%)</label>
                                    <input type="number" class="form-control" name="product_order_tax" min="0" value="{{ old('product_order_tax', $product->product_order_tax) }}">
                                </div>
                                <div class="col-md-6">
                                    <label for="product_tax_type">Tax type</label>
                                    <select class="form-control" name="product_tax_type" id="product_tax_type">
                                        <option value="" selected>Select Tax Type</option>
                                        <option value="1" {{ $product->product_tax_type == 1 ? 'selected' : '' }}>Exclusive</option>
                                        <option value="2" {{ $product->product_tax_type == 2 ? 'selected' : '' }}>Inclusive</option>
                                    </select>
                                </div>
                            </div>

                            {{-- Note --}}
                            <div class="form-group">
                                <label for="product_note">Note</label>
                                <textarea name="product_note" id="product_note" rows="4" class="form-control">{{ old('product_note', $product->product_note) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Images --}}
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="form-group">
                                <label>Product Images</label>
                                <div class="dropzone d-flex flex-wrap align-items-center justify-content-center" id="document-dropzone">
                                    <div class="dz-message" data-dz-message>
                                        <i class="bi bi-cloud-arrow-up"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    {{-- Modal Create Category --}}
    @include('product::includes.category-modal')
@endsection

@section('third_party_scripts')
    <script src="{{ asset('js/dropzone.js') }}"></script>
@endsection

@push('page_scripts')
    <script>
        var uploadedDocumentMap = {}
        Dropzone.options.documentDropzone = {
            url: '{{ route('dropzone.upload') }}',
            maxFilesize: 1,
            acceptedFiles: '.jpg,.jpeg,.png',
            maxFiles: 3,
            addRemoveLinks: true,
            dictRemoveFile: "<i class='bi bi-x-circle text-danger'></i> remove",
            headers: {
                'X-CSRF-TOKEN': "{{ csrf_token() }}"
            },
            success: function (file, response) {
                $('form').append('<input type="hidden" name="document[]" value="' + response.name + '">');
                uploadedDocumentMap[file.name] = response.name;
            },
            removedfile: function (file) {
                file.previewElement.remove();
                var name = file.file_name || uploadedDocumentMap[file.name];
                $('form').find('input[name="document[]"][value="' + name + '"]').remove();
            },
            init: function () {
                @if(isset($product) && $product->getMedia('images'))
                var files = {!! json_encode($product->getMedia('images')) !!};
                for (var i in files) {
                    var file = files[i];
                    this.options.addedfile.call(this, file);
                    this.options.thumbnail.call(this, file, file.original_url);
                    file.previewElement.classList.add('dz-complete');
                    $('form').append('<input type="hidden" name="document[]" value="' + file.file_name + '">');
                }
                @endif
            }
        }
    </script>

    <script src="{{ asset('js/jquery-mask-money.js') }}"></script>
    <script>
        $(document).ready(function () {
            $('#product_price').maskMoney({
                prefix:'{{ settings()->currency->symbol }} ',
                thousands:'{{ settings()->currency->thousand_separator }}',
                decimal:'{{ settings()->currency->decimal_separator }}',
                allowZero: true,
                precision: 0
            });

            $('#product-form').submit(function () {
                $('#product_price').maskMoney('destroy');
                var product_price = $('#product_price').val().replace(/[^\d]/g, '');
                $('#product_price').val(product_price);
            });
        });
    </script>
@endpush
