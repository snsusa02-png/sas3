@if(isset($recs) and !is_null($recs) and count($recs)>0)
    <div class="row">
        <div class="col-xl-6 col-lg-6 col-md-8">
            {{$recs->links()}}
        </div>

        @if(isset($data->pageitmcnts))
            <div class="input-group col-md-4 col-lg-2 col-xl-3 ">
                <div class="mx-auto my-2" width="100%">
                    <span for="" class="small" style="color: #6c757d;">Записей на странице:&nbsp;</span>
                    {!! Form::select('s_pageitmcnt', $data->pageitmcnts??[], $search_params['s_pageitmcnt'] ?? '10'
                    , [
                    'class' => 'inline-form-control small',
                    'style' => 'color:gray; max-width:86px; text-align-last:right; border: 1px solid #dee2e6',
                    'onChange' => 'this.form.submit()',
                    'title' => 'записей на странице',
                    ]) !!}
                </div>
            </div>
        @endif
    </div>
@endif
