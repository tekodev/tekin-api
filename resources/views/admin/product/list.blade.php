{{-- ADD HTML SMALL MODAL - BEGIN --}}
@extends('_template_adm.modal_small')
{{-- SMALL MODAL CONFIG --}}
@section('small_modal_title', ucwords(lang('import', $translation)).' Excel')
@section('small_modal_content')
  <label>{{ lang('Browse the file', $translation) }}</label>
  <div class="form-group">
    <input type="file" name="file" required="required" accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel">
  </div>
@endsection
@section('small_modal_btn_label', ucwords(lang('import', $translation)))
@section('small_modal_btn_onclick', "$('.btn-submit').addClass('disabled');$('.btn-submit').html('<i class=\"fa fa-spin fa-spinner\"></i>&nbsp; ".lang('Loading, please wait..', $translation)."');")
@section('small_modal_form', true)
@section('small_modal_method', 'POST')
@section('small_modal_url', route('admin.product.import_excel'))
{{-- ADD HTML SMALL MODAL - END --}}

@extends('_template_adm.master')

@php
  // USE LIBRARIES
  use App\Libraries\Helper;

  $this_object = ucwords(lang('product', $translation));
  $this_module = 'Product';

  if(isset($data)){
    $pagetitle = $this_object;
    $link_get_data = route('admin.product.get_data');
    $function_get_data = 'refresh_data();';
  }else{
    $pagetitle = ucwords(lang('deleted #item', $translation, ['#item' => $this_object]));
    $link_get_data = route('admin.product.get_data_deleted');
    $function_get_data = 'refresh_data_deleted();';
  }
@endphp

@section('title', $pagetitle)

@section('content')
  <div class="">
    <!-- message info -->
    @include('_template_adm.message')

    <div class="page-title">
      <div class="title_left">
        <h3>{{ $pagetitle }}</h3>
      </div>

      @if (isset($data))
        <div class="title_right">
          <div class="col-md-5 col-sm-5 col-xs-12 form-group pull-right">
            @if (Helper::authorizing('Product', 'Restore')['status'] == 'true')
              <a href="{{ route('admin.product.deleted') }}" class="btn btn-round btn-danger" style="float: right; margin-bottom: 5px;" data-toggle="tooltip" title="{{ ucwords(lang('view deleted items', $translation)) }}">
                <i class="fa fa-trash"></i>
              </a>
            @endif
            <a href="{{ route('admin.product.create') }}" class="btn btn-round btn-success" style="float: right;">
              <i class="fa fa-plus-circle"></i>&nbsp; {{ ucwords(lang('add new', $translation)) }}
            </a>
          </div>
        </div>
      @else
        <div class="title_right">
          <div class="col-md-5 col-sm-5 col-xs-12 form-group pull-right">
            <a href="{{ route('admin.product.list') }}" class="btn btn-round btn-primary" style="float: right;">
              <i class="fa fa-check-circle"></i>&nbsp; {{ ucwords(lang('active items', $translation)) }}
            </a>
          </div>
        </div>  
      @endif

      @if (isset($data))
        <div class="title_left">
          @if (Helper::authorizing($this_module, 'Export Excel')['status'] == 'true')
            <a href="{{ route('admin.product.export_excel') }}" class="btn btn-round btn-warning" style="margin: 10px 0;" target="_blank">
              <i class="fa fa-cloud-download"></i>&nbsp; {{ ucwords(lang('export', $translation)) }} Excel
            </a>
          @else
            &nbsp;
          @endif

          @if (Helper::authorizing($this_module, 'Import Excel')['status'] == 'true')
            <a href="{{ route('admin.product.import_excel_template') }}" class="btn btn-round btn-info" style="margin: 10px 0;" target="_blank">
              <i class="fa fa-download"></i>&nbsp; {{ lang('Download template for Import', $translation) }} Excel
            </a>
          @endif
        </div>
        <div class="title_right">
          <div class="col-md-5 col-sm-5 col-xs-12 form-group pull-right">
            @if (Helper::authorizing($this_module, 'Import Excel')['status'] == 'true')
              <button type="button" class="btn btn-primary btn-round" data-toggle="modal" data-target=".bs-modal-sm" style="float: right;">
                <i class="fa fa-cloud-upload"></i>&nbsp; {{ ucwords(lang('import', $translation)) }} Excel
              </button>
            @else
              &nbsp;
            @endif
          </div>
        </div>
      @endif
    </div>

    <div class="clearfix"></div>

    <div class="row">
      <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="x_panel">
          <div class="x_title">
            <h2>{{ ucwords(lang('data list', $translation)) }}</h2>
            <div class="clearfix"></div>
          </div>
          <div class="x_content">
            <div class="table-responsive">
              <table class="table table-striped table-bordered">
                <thead>
                  <tr>
                    <th>{{ ucwords(lang('title', $translation)) }}</th>
                    <th>{{ ucwords(lang('subtitle', $translation)) }}</th>
                    <th>{{ ucwords(lang('category', $translation)) }}</th>
                    <th>{{ ucwords(lang('image', $translation)) }}</th>
                    <th>{{ ucwords(lang('status', $translation)) }}</th>
                    <th>{{ ucwords(lang('created', $translation)) }}</th>
                    <th>{{ ucwords(lang('last updated', $translation)) }}</th>
                    <th>{{ ucwords(lang('action', $translation)) }}</th>
                  </tr>
                </thead>
                @if (isset($data) && count($data) > 0)
                  <tbody class="sorted_table">
                    @foreach ($data as $item)
                      <tr role="row" id="row-{{ $item->id }}" title="{{ ucfirst(lang("Drag & drop to sorting", $translation)) }}" data-toggle="tooltip">
                        <td class="dragndrop">{{ $item->title }}</td>
                        <td>{{ $item->subtitle }}</td>
                        <td>{{ $item->category ? $item->category->title : '-' }}</td>
                        <td>
                            @if($item->image)
                                <img src="{{ asset($item->image) }}" style="max-width:100px;">
                            @else
                                -
                            @endif
                        </td>
                        <td>
                          @if ($item->status != 1)
                            <span class="label label-danger"><i>{{ ucwords(lang('disabled', $translation)) }}</i></span>
                          @else
                            <span class="label label-success">{{ ucwords(lang('enabled', $translation)) }}</span>
                          @endif
                        </td>
                        <td>{{ $item->created_at }}</td>
                        <td>{{ Helper::time_ago(strtotime($item->updated_at), lang('ago', $translation), Helper::get_periods($translation)) }}</td>
                        <td>
                          <a href="{{ route('admin.product.edit', $item->id) }}" class="btn btn-xs btn-primary" title="{{ ucwords(lang('edit', $translation)) }}">
                            <i class="fa fa-pencil"></i>&nbsp; {{ ucwords(lang('edit', $translation)) }}
                          </a>
                          <form action="{{ route('admin.product.delete') }}" method="POST" onsubmit="return confirm('{{ lang('Are you sure to delete this #item?', $translation, ['#item'=>$this_object]) }}');" style="display: inline">
                            {{ csrf_field() }}
                            <input type="hidden" name="id" value="{{ $item->id }}">
                            <button type="submit" class="btn btn-xs btn-danger" title="{{ ucwords(lang('delete', $translation)) }}">
                              <i class="fa fa-trash"></i>&nbsp; {{ ucwords(lang('delete', $translation)) }}
                            </button>
                          </form>
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                @else
                  <tbody>
                    <tr>
                      <td colspan="8"><h2 class="text-center">{{ strtoupper(lang('no data available', $translation)) }}</h2></td>
                    </tr>
                  </tbody>
                @endif
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection

@section('css')
  <!-- Sortable-Table -->
  @include('_form_element.sortable_table.css')
@endsection

@section('script')
  <script>
    var AjaxSortingURL = '{{ route("admin.product.sorting") }}';
  </script>
  <!-- Sortable-Table -->
  @include('_form_element.sortable_table.script')
@endsection