/**
 * Poll
 *
 * @param array object options
 */
Poll = function (options) {
    /**
     * Poll options
     *
     * @var array object
     */
    var pollOptions = {
        "csrf" : null,
        "url" : null,
        "wrapper_id": null,
        "values_wrapper" : null,
        "links" : null,
        "not_active_link" : null
    };

    // extend options
    pollOptions = $.extend({}, pollOptions, options);

    /**
     * Init
     */
    var init = function () {
        initPollInputs();
        initPollLinks();
    }

    /**
     * Init poll links
     */
    var initPollLinks = function() {
        $(pollOptions.wrapper_id).find(pollOptions.links).click(function(e){
            e.preventDefault();
            $(this).parent().find(pollOptions.links).toggleClass(pollOptions.not_active_link);
            var  $container = getValuesContainer();

            switch($(this).attr("data-action")) {
                case "answers" :
                    // get answers
                    ajaxQuery($container, pollOptions.url, function(data){
                        data =  $.parseJSON(data);
                        $container.html(data.data);

                        initPollInputs();
                    }, "get", {
                        "widget_action" : "get_answers"
                    }, false);
                    break;

                case "result" :
                default :
                    // get results
                    ajaxQuery($container, pollOptions.url, function(data){
                        data =  $.parseJSON(data);
                        $container.html(data.data);
                    }, "get", {
                        "widget_action" : "get_results"
                    }, false);
            }
        });
    }

    /**
     * Init poll inputs
     */
    var initPollInputs = function() {
        $(pollOptions.wrapper_id).find(":input").unbind().click(function () {
            var  $container = getValuesContainer();
            $(pollOptions.wrapper_id).find(pollOptions.links).toggleClass(pollOptions.not_active_link);

            // make a vote and show result
            ajaxQuery($container, pollOptions.url, function(data){
                data =  $.parseJSON(data);
                $container.html(data.data);
            }, "post", {
                "widget_action" : "make_vote",
                "answer_id" : $(this).attr("data-id"),
                "csrf" : pollOptions.csrf
            }, false);
        });
    }

    /**
     * Get values container
     *
     * @return object jquery
     */
    var getValuesContainer = function()
    {
        return $(pollOptions.wrapper_id).find(pollOptions.values_wrapper);
    }

    init();
}