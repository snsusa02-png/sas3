$(document).ready(function () {

    // $(function () {
    //     $('.table-wrapper tr').each(function () {
    //         var tr = $(this),
    //             h = 0;
    //         tr.children().each(function () {
    //             var td = $(this),
    //                 tdh = td.height();
    //             if (tdh > h) h = tdh;
    //         });
    //         tr.css({height: h + 'px'});
    //     });
    // });


    function app_handle_listing_horisontal_scroll(listing_obj)
    {
        //get table object
        table_obj = $('.table',listing_obj);

        //get count fixed collumns params
        count_fixed_collumns = table_obj.attr('data-count-fixed-columns')

        if(count_fixed_collumns>0)
        {
            //get wrapper object
            wrapper_obj = $('.table-scrollable',listing_obj);

            wrapper_left_margin = 0;

            table_collumns_width = new Array();
            table_collumns_margin = new Array();

            //calculate wrapper margin and fixed column width
            $('th',table_obj).each(function(index){
                if(index<count_fixed_collumns)
                {
                    wrapper_left_margin += $(this).outerWidth();
                    table_collumns_width[index] = $(this).outerWidth();
                }
            })

            //calcualte margin for each column
            $.each( table_collumns_width, function( key, value ) {
                if(key==0)
                {
                    table_collumns_margin[key] = wrapper_left_margin;
                }
                else
                {
                    next_margin = 0;
                    $.each( table_collumns_width, function( key_next, value_next ) {
                        if(key_next<key)
                        {
                            next_margin += value_next;
                        }
                    });

                    table_collumns_margin[key] = wrapper_left_margin-next_margin;
                }
            });

            //set wrapper margin
            if(wrapper_left_margin>0)
            {
                wrapper_obj.css('cssText','margin-left:'+wrapper_left_margin+'px !important; width: auto')
            }

            //set position for fixed columns
            $('tr',table_obj).each(function(){

                //get current row height
                current_row_height = $(this).outerHeight();

                $('th,td',$(this)).each(function(index){

                    //set row height for all cells
                    $(this).css('height',current_row_height)

                    //set position
                    if(index<count_fixed_collumns)
                    {
                        $(this).css('position','absolute')
                            .css('margin-left','-'+table_collumns_margin[index]+'px')
                            .css('width',table_collumns_width[index])

                        $(this).addClass('table-fixed-cell')
                    }
                })
            })
        }
    }

    $(function(){
        app_handle_listing_horisontal_scroll($('#table-listing'))
    })





});
