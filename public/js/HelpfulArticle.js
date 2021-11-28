$(document).ready(function () {

    // url = document.location.href;
    // url = url.replace("://", "")
    // pos = url.indexOf('/')
    // tag = url.substr(pos)
    //
    // getArticle(tag)


    helptags = $(".helptags").attr("data");
    console.log('helptags=', helptags);
    if (helptags) {
        $("#btnGetHelp").show(300);
    }

});

var articles = "";

function getArticle(tag) {
    if (articles == "") {
        $.ajax({
            url: "http://itqua.ru/tst.php",
            type: "post",
            dataType: "json",
            data: {
                "cat": "7",
                "tag": tag
            },
            success: function (data) {
                articles = data;
                //console.log('articles=', articles);
                if (articles.length)
                    showArticles();
                else
                    alert('Материалы не найдены.');
            }
        })
    } else {
        showArticles()
    }


}

function showArticles() {
    $("#ArticleTitle").html("Полезная информация");
    $("#ArticleText").empty()
    $(".modal-dialog").css("max-width", '500px')
    articles.forEach(function (el) {
        $("#ArticleText")
            .append(
                $("<li>")
                    .attr("articleID", el.id)
                    .attr("onClick", "showArticle(this)")
                    .css('cursor', 'pointer')
                    .css('font-weight', 'bold')
                    .css('color', '#0077b5')
                    .append(el.title)
            )
    })
    $('.modal').show(333, 'swing')
}

function showArticle(art) {
    $("#ArticleText").empty()
    $(".modal-dialog").css("max-width", '1000px')
    var articleID = $(art).attr("articleID")
    //console.log(articles)
    articles.forEach(function (el) {

        if (articleID == el.id) {
            $("#ArticleTitle").html(el.title);
            $("#ArticleText").html(el.content)
        }
    })
}

function getArticleText() {

    // url = document.location.href;
    // url = url.replace("://", "")
    // pos = url.indexOf('/')
    // tag = url.substr(pos)

    // var tag = ""
    // $(".helptags").each(function (el) {
    //     tag = tag + ",help_" + $(this).attr("data")
    // })

    var tag = "";
    $('.helptags').each(function () {
        var arr = $(this).attr("data").split(",");
        arr.forEach(function (item, i) {
            if (item.length > 0)
                tag = tag + ",help_" + item;
        });
    });

    tag = tag.substring(1)
        //console.log(tag)
    if (tag)
        getArticle(tag);
}
