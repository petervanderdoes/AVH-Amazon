jQuery(document).ready(function(a){a("#printOptions a").click(function(){var b=a(this).attr("href");a(this).parent().addClass("avhamazon-tabs-selected").siblings("li").removeClass("avhamazon-tabs-selected");a(this).parent().parent().siblings(".avhamazon-tabs-panel").hide();a(b).show()})});