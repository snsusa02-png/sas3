@if( 1==1 and isset($contract) and ($contract->id!=-1))


    <!-- Comments Form -->
    <div class="card my-4">
        <h5 class="card-header">Прокомментировать:</h5>
        <div class="card-body">
            @if(Auth::guest())
                <p>Зарегистрируйтесь для комментирования</p>
            @else
                <div class="">
                    <form method="post" action="{{route('obj_comments.add')}}">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <input type="hidden" name="sysobjid" value="151">
                        <input type="hidden" name="objid" value="{{ $contract->id }}">
                        <div class="form-group">
						<textarea required="required" placeholder="Пишите здесь" name="body" rows="3"
                                  class="form-control"></textarea>
                        </div>
                        <input type="submit" name='post_comment' class="btn btn-success"
                               value="Добавить"/>
                    </form>
                </div>
            @endif

        </div>
    </div>


    @if($contract->comments)
        <div class=" card my-1">
            <div class="card-header">
			<span data-toggle="collapse show" data-target="#comments">
                <i class="fa fa-comments-o text-info" aria-hidden="true"></i> Комментарии</span>

                <div class="float-right">
                    @if (count($contract->comments)>0)
                        <button data-toggle="collapse" data-target="#comments"
                                class="btn btn-light btn-sm "><i class="fa fa-eye-slash" aria-hidden="true"></i>
                        </button>
                    @endif
                </div>
            </div>

            <div class="card-body collapse show" id="comments">
            @foreach($contract->comments as $comment)
                <!-- Single Comment -->
                    <?php
                    $avatar_img = $comment->author->image ?? '/images/signs/user-no-photo.jpg';
                    ?>
                    <div class="media mb-4">
                        <img class="d-flex mr-3 rounded-circle" style="max-width: 50px;height: auto;"
                             src="{{$avatar_img}}" alt="">
                        <div class="media-body">
                            <span class="mt-0">{{ $comment->author->fullname }}</span>
                            <span class="float-right">
                                <time datetime=""
                                      class="article-data small"> {{ $comment->created_at->format('d.m.Y в H:i') }}</time>
                            </span>
                            <div class="font-italic font-weight-bold">
                                {!! $comment->body !!}
                            </div>
                        </div>
                    </div>
                @endforeach

                @if(1==0)

                <!-- Comment with nested comments -->
                    <div class="media mb-4">
                        <img class="d-flex mr-3 rounded-circle" src="http://placehold.it/50x50" alt="">
                        <div class="media-body">
                            <h5 class="mt-0">Commenter Name</h5>
                            Cras sit amet nibh libero, in gravida nulla. Nulla vel metus scelerisque ante
                            sollicitudin.
                            Cras purus odio, vestibulum in vulputate at, tempus viverra turpis. Fusce
                            condimentum
                            nunc
                            ac nisi vulputate fringilla. Donec lacinia congue felis in faucibus.

                            <div class="media mt-4">
                                <img class="d-flex mr-3 rounded-circle" src="http://placehold.it/50x50" alt="">
                                <div class="media-body">
                                    <h5 class="mt-0">Commenter Name</h5>
                                    Cras sit amet nibh libero, in gravida nulla. Nulla vel metus scelerisque
                                    ante
                                    sollicitudin. Cras purus odio, vestibulum in vulputate at, tempus viverra
                                    turpis.
                                    Fusce condimentum nunc ac nisi vulputate fringilla. Donec lacinia congue
                                    felis
                                    in
                                    faucibus.
                                </div>
                            </div>

                            <div class="media mt-4">
                                <img class="d-flex mr-3 rounded-circle" src="http://placehold.it/50x50" alt="">
                                <div class="media-body">
                                    <h5 class="mt-0">Commenter Name</h5>
                                    Cras sit amet nibh libero, in gravida nulla. Nulla vel metus scelerisque
                                    ante
                                    sollicitudin. Cras purus odio, vestibulum in vulputate at, tempus viverra
                                    turpis.
                                    Fusce condimentum nunc ac nisi vulputate fringilla. Donec lacinia congue
                                    felis
                                    in
                                    faucibus.
                                </div>
                            </div>

                        </div>
                    </div>
                @endif

            </div>

            <div class="card-footer">
                <div class="float-right small" style="color:gray;"><i class="fa fa-comments-o"
                                                                      aria-hidden="true"></i>: {{count($contract->comments)}}
                </div>
            </div>
        </div>
    @endif


@endif
