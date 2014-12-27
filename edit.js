$('a.swipebox').swipebox({
    hideBarsDelay: -1,
    afterOpen: function(){
        var ui = $.swipebox.extend();
        var close = $('#swipebox-close');
        var fs = $('<i class="fa fa-arrows-alt"></i>');
        fs.on('click',function(){
            var elem = $('#swipebox-overlay')[0];
            if (elem.requestFullscreen) {
                elem.requestFullscreen();
            } else if (elem.msRequestFullscreen) {
                elem.msRequestFullscreen();
            } else if (elem.mozRequestFullScreen) {
                elem.mozRequestFullScreen();
            } else if (elem.webkitRequestFullscreen) {
                elem.webkitRequestFullscreen();
            }
        });

        var pp = $('<i id="ppbutton" class="fa fa-play"></i>');
        pp.on('click',function(e){
            var button = $(e.target);
            if(button.hasClass('fa-play')){
                button.removeClass('fa-play').addClass('fa-pause');
                button.attr('data-intid',window.setInterval(function(){ui.getNext()},5000));
            }else{
                button.removeClass('fa-pause').addClass('fa-play');
                window.clearInterval(button.attr('data-intid'));
                button.attr('data-intid','');
            }
        });
        var ctrlbox = $("<div id='ctrlbox'>");
        ctrlbox.append(pp);
        ctrlbox.append(fs);
        close.after(ctrlbox);

        // Play/pause button
        // Spacebar/Enter advances
        // big Fullscreen
    },
    afterClose: function(){
        window.clearInterval($('#ppbutton').attr('data-intid'));
    }
});

$('#navchange').on('change',function(e){
    document.location.search = 'd=' + e.target.value;
});

$(document).on('keyup',function(e){
    if((e.keyCode == 32 || e.keyCode == 13) && $('#swipebox-overlay').length > 0){
        $.swipebox.extend().getNext();
    }
});
