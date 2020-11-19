$(document).ready(function() {
    /* CAMPAIGN DETAILS PAGE JS STARTS */
    $('.stats_tabbing a').click(function(e){
        e.preventDefault();
        $('.stats_tabbing li').removeClass('active');
        $(this).closest('li').addClass('active');
        $('#stats_tab_content .tab').removeClass('active');
        var curr_tabed = $(this).attr('href');
        $(curr_tabed).addClass('active');
    });
    $('.search_opener').click(function(){
        $(this).closest('.state_header').find('.table_search').toggleClass('active')
    });
    /* CAMPAIGN DETAILS PAGE JS ENDS */
    $('.location_droper').click(function(){
        $(this).closest('li').find('.second_level').slideToggle();
        $(this).closest('li').prevAll('li').find('.second_level').slideUp();
        $(this).closest('li').nextAll('li').find('.second_level').slideUp();
    });
    $('.checks_toggler').click(function(){
        $('.checks_list').slideToggle();
    });
    $("#filter_check").click(function () {
        $('.checks_list').find('input:checkbox').not(this).prop('checked', this.checked);
    });

    //APP GROUP STEPS FLOW
    $('.skip_step').click(function(){
        $('.modal, .modal-backdrop').removeClass('in');
        $('body').removeClass('modal-open');
        $('body').removeClass('modal-open');
    });
    $(".device_selection").on('change', function () {
    });
    $(".device_selection").on('change', function () {
        var option_class = this.options[this.selectedIndex].id;
        if( option_class == "iphone_mob"){
            $('.preview_skin').removeClass('android_tablet, android_mob, ipad, android_tablet, android_mob, ipad, android_mob');
        }
        if( option_class == "samsung_mob"){
            $('.preview_skin').removeClass('android_tablet, ipad, android_mob, ipad, android_tablet, ipad');
            $('.preview_skin').addClass('android_mob')
        }
        if( option_class == "ipad"){
            $('.preview_skin').removeClass('android_tablet, ipad, android_mob, android_tablet');
            $('.preview_skin').addClass('ipad')
        }
        if( option_class == "samsung_tab"){
            $('.preview_skin').removeClass('iphone_skin, ipad, android_mob, ipad');
            $('.preview_skin').addClass('android_tablet')
        }
    });
    $('.variant_droper').click(function(e){
        e.preventDefault();
        $(this).toggleClass('active');
        $('.variant_drop ul').slideToggle();
    });
    $(document).click(function(e) {
        var variant_droper = $('.variant_droper');
        if (!variant_droper.is(e.target) && variant_droper.has(e.target).length === 0)  {
            variant_droper.removeClass('active');
            variant_droper.closest('div').find('ul').slideUp();
        }
    });
    
    $('.map_caption a').click(function(e){
        e.preventDefault();
    });
    $('.info_opener').click(function(){
        $('.map_form').toggleClass('active');
    });
    $(document).click(function(e) {
        var info_opener = $('.info_opener');
        if (!info_opener.is(e.target) && info_opener.has(e.target).length === 0)  {
            info_opener.closest('.map_form').removeClass('active');
        }
    });

    $('.languages_holder strong').click(function(){
        $('.flags.dropdown').slideToggle();
    });
    $(document).click(function(e) {
        var language_droper = $('.languages_holder strong');
        if (!language_droper.is(e.target) && language_droper.has(e.target).length === 0)  {
            language_droper.removeClass('active');
            language_droper.closest('div').find('.flags.dropdown').slideUp();
        }
    });




    $('.login_form').addClass('active');
    // SAME HEIGHT BLOCKS
    function same_height(){
        var class_name = ".same-height";
        var max_height = 0;
        $(class_name).each(function(index, data){
            if($(data).height() > max_height){
                max_height = $(data).height();
            }
        });
        $(class_name).height(max_height);
    }
    same_height();
    //USER PROFILE DROPDOWN IN HEADER
    $('.drop_opener').click(function(e){
        e.preventDefault();
        $(this).toggleClass('active');
        $('.profile_dropdown').slideToggle('100');
    });
    
    $(document).click(function(e) {
        var drop_opener = $('.drop_opener');
        if (!drop_opener.is(e.target) && drop_opener.has(e.target).length === 0)  {
            drop_opener.removeClass('active');
            drop_opener.closest('div').find('.profile_dropdown').slideUp();
        }
    });



    $('.inner_drop_opener').click(function(){
        $(this).closest('.profile_dropdown').show("slow");
    });
    $('.profile_dropdown a').click(function(e){
        e.preventDefault();
        $('.profile_dropdown').slideUp('100');
    });

    //SIDEBAR TOGGLING
    $('.nav_opener').click(function(e){
        e.preventDefault();
        $('#sidebar, .page_content_holder').toggleClass('toggle');
    });
    //LEFT INNER SIDEBAR TAGS DROPDOWNS IN TABLE PAGES
    $('.side_tags .opener').click(function(e){
        e.preventDefault();
        $(this).closest('li').find('.inner_tags').slideToggle('100');
        $(this).find('.fa-sort-down').toggleClass('active');
    });
    $('.inner_tags li a').click(function(e){
        e.preventDefault();
        $(this).closest('li').find('.inner_tags').slideUp('100');
    });
    $('.misc_check').click(function(){
        $(this).closest('.extra_checks').find('.misc_check').removeClass('active')
        $(this).closest('div').find(this).addClass('active')
    });
    //ACTIONS TOGGLER ON CAMPAIGNS PAGE
    $('.actions_droper').click(function(){
        $(this).closest('div').find('.actions_drop').slideToggle('100');
    });
    $(document).click(function(e) {
        var actions_droper = $('.actions_droper');
        if (!actions_droper.is(e.target) && actions_droper.has(e.target).length === 0)  {
            actions_droper.removeClass('active');
            actions_droper.closest('div').find('.actions_drop').slideUp();
        }
    });
    //TABBING ON CREATE CAMPAIGN PAGE
    $('.tabs a').click(function(e){
        e.preventDefault();
        var current_tab = $(this).attr('href');
        $('.tabs li').removeClass('active');
        $(this).parent('li').addClass('active');
        $('#tab-content .tab').removeClass('active');
        $(current_tab).addClass('active');
        setTimeout(function(){ 
            var class_name = ".same-height";
        var max_height = 0;
        $(class_name).each(function(index, data){
            if($(data).height() > max_height){
                max_height = $(data).height();
            }
        });
        $(class_name).height(max_height);
        }, 1000);
    });
    $('.inner_tabs a').click(function(e){
        e.preventDefault();
        var current_tab = $(this).attr('href');
        $('.inner_tabs li').removeClass('active');
        $(this).parent('li').addClass('active');
        $('.inner_tab_content .inner_tab_text').removeClass('active');
        $(current_tab).addClass('active');
    });
    $('.compose_tabs a').click(function(e){
        e.preventDefault();
        var current_tab = $(this).attr('href');
        $('.compose_tabs li').removeClass('active');
         $(this).parent('li').addClass('active');
        $('.compose_tab_content .tab_content').removeClass('active');
        $(current_tab).addClass('active');
    });
    var table_tr_count = $('table tr').length;
        if(table_tr_count >= 3){
        $('table tr:last-child, table tr:nth-last-child(2)').addClass('overflow_tr');
    }

    //EDIT/VIEW CAMPAIGN OPTIONS TOGGLE
    var trigger = $('.table_drop').closest('td');
    $(trigger).click(function(){
        // $('.table_drop').slideUp('100');
        $(this).closest('tr').nextAll('tr').find('.table_drop').slideUp();
        $(this).closest('tr').prevAll('tr').find('.table_drop').slideUp();
        $(this).find('.table_drop').slideToggle('100');
    });
    $(document).on("click", function(event){
        if(trigger !== event.target && !trigger.has(event.target).length){
            $(".table_drop").slideUp("fast");
        }
    });
    //SELECT TEMPLATE IN CREATE CAMPAIGN PAGE
    $('.templates_sample a').click(function(e){
        e.preventDefault();
       $('.templates_sample li').removeClass('active');
       $(this).closest('li').addClass('active');
    });
    $('.actions_drop a').click(function(){
       $(this).closest('ul').slideUp();
    });
    //SAVED TEMPLATE SELECTION IN CREATE CAMPAIGN PAGE
    $('.saved_temps a').click(function(e){
        e.preventDefault();
       $('.saved_temps li').removeClass('active');
       $(this).closest('li').addClass('active');
    });
    $('.total_col').hover(function(){
        $(this).find('.total_drop').toggleClass('active');
    });
    //DATE PICKER
    // $( function() {
    //     $( ".datepicker" ).datepicker();
    // });

    //ON -s OFF BUTTON 
    $( ".an-switch" ).each(function( count ) {
        var raw_checkbox = $(this);
        var existing_id = $(this).attr('id');
        if(existing_id === undefined)
            id = 'an-onoffswitch'+count;
        else
            id = existing_id;

        var addClass = $(this).addClass('an-onoffswitch-checkbox');
        var addId = $(addClass).attr('id', id);
        var checkBox = $(addId).wrap('<span>').parent().html();
        $(raw_checkbox).unwrap().replaceWith(
            "<div class='an-onoffswitchContainer'>"+
            checkBox+
            "<label class='an-onoffswitch-label' for='"+id+"'>"+
            "<span class='an-onoffswitch-inner'></span>"+
            "<span class='an-onoffswitch-switch'></span>"+
            "</label>"+
            "</div>"
        );
    });
    $('.inner_drop_opener').click(function(e){
        e.preventDefault();
        $(this).closest('li').find('ul').slideToggle();
        $(this).closest('li').nextAll('li').find('.left_inner_menu').hide();
        $(this).closest('li').prevAll('li').find('.left_inner_menu').hide();
    });
    $(document).click(function(e) {
        var inner_drop_opener = $('.inner_drop_opener');
        if (!inner_drop_opener.is(e.target) && inner_drop_opener.has(e.target).length === 0)  {
            inner_drop_opener.removeClass('active');
            inner_drop_opener.closest('li').find('.left_inner_menu').slideUp();
        }
    });
}); //Document Ready Ends Here
 /*************** CHARTS OPTIONS  ****************/

window.onload = function () {

    var options = {
        animationEnabled: true,
        exportEnabled: true,
        theme: "light1", // "light1", "light2", "dark1", "dark2"
        title:{
            text: ""
        },
        dataPointWidth: 25,
        data: [{
            type: "column", //change type to bar, line, area, pie, etc
            //indexLabel: "{y}", //Shows y value on all Data Points
            indexLabelFontColor: "#5A5757",
            indexLabelPlacement: "outside",
            dataPoints: [
                { x: 10, y: 71, color: "#2a8689" },
                { x: 13, y: 55, color: "#ff2c4d" },
                { x: 16, y: 50, color: "#f4d63a" },

                { x: 25, y: 65, color: "#2a8689" },
                { x: 28, y: 92, indexLabel: "Highest", color: "#ff2c4d" },
                { x: 31, y: 68, color: "#f4d63a" },

                { x: 40, y: 38, color: "#2a8689" },
                { x: 43, y: 71, color: "#ff2c4d" },
                { x: 46, y: 54, color: "#f4d63a" },

                { x: 55, y: 60, color: "#2a8689" },
                { x: 58, y: 36, color: "#ff2c4d" },
                { x: 61, y: 49, color: "#f4d63a" },

                { x: 70, y: 60, color: "#2a8689" },
                { x: 73, y: 36, color: "#ff2c4d" },
                { x: 76, y: 49, color: "#f4d63a" },

                { x: 85, y: 60, color: "#2a8689" },
                { x: 88, y: 36, color: "#ff2c4d" },
                { x: 91, y: 21, indexLabel: "Lowest", color: "#f4d63a" },

                { x: 100, y: 60, color: "#2a8689" },
                { x: 103, y: 36, color: "#ff2c4d" },
                { x: 106, y: 21, indexLabel: "Lowest", color: "#f4d63a" }
            ]
        }]
    }
    var options2 = {
        animationEnabled: true,
        exportEnabled: true,
        theme: "light1", // "light1", "light2", "dark1", "dark2"
        title:{
            text: ""
        },
        dataPointWidth: 25,
        data: [{
            type: "column", //change type to bar, line, area, pie, etc
            //indexLabel: "{y}", //Shows y value on all Data Points
            indexLabelFontColor: "#5A5757",
            indexLabelPlacement: "outside",
            dataPoints: [
                { x: 10, y: 71, color: "#00a6d0" },
                { x: 13, y: 51, color: "#fd8642" },

                { x: 25, y: 65, color: "#00a6d0" },
                { x: 28, y: 25, color: "#fd8642" },

                { x: 40, y: 69, color: "#00a6d0" },
                { x: 43, y: 47, color: "#fd8642" },

                { x: 55, y: 63, color: "#00a6d0" },
                { x: 58, y: 32, color: "#fd8642" },

                { x: 70, y: 25, color: "#00a6d0" },
                { x: 73, y: 85, color: "#fd8642" },

                { x: 85, y: 66, color: "#00a6d0" },
                { x: 88, y: 74, color: "#fd8642" },

                { x: 100, y: 58, color: "#00a6d0" },
                { x: 103, y: 30, color: "#fd8642" }
            ]
        }]
    }

    
    
    $("#chart1").CanvasJSChart(options);
    $("#chart2").CanvasJSChart(options2);
}
$(document).ready(function() {
    var table = $('#example').DataTable();
     
    // Event listener to the two range filtering inputs to redraw on input
    $('input').change( function() {
        table.draw();
    });
});

