$('input[name=c1], input[name=c2]').on('keyup', function(e){
    if(e.keyCode == 13)
        get($(this).attr('name'), $(this).val());
});

function get(name, val){
    var $this = $('#' + name);
    var $other = $('#' + (name === 'c1' ? 'c2' : 'c1'));
    $this.find('.photo').css({
        'background-image':'url(\'img/loader.gif\')',
        'background-position':'center center',
        'background-size':'auto'
    });
    //console.log($('#'+$(this).attr('name')))
    $.getJSON('./index.php?q=' + val, function(data) {
        
        $this.find('.photo').css({
            'background-image':'url(\''+data.Wikipedia.Photo+'\')',
            'background-position':'top center',
            'background-size':'cover'
        });
        //console.log('data',data);
        var rank = data.Rank;
        var rankOther = isNaN($other.find('.numbars').data('rank')) ? 0 : $other.find('.numbars').data('rank') *1;
        var color = rank > rankOther ? 'green' : 'red';
        var otherColor = color === 'red' ? 'green' : 'red';
        //var top = data.TopRank;
        //var topOther = isNaN($other.find('.numbars').data('top')) ? 0 : $other.find('.numbars').data('top') *1;
        //top = top > topOther ? top : topOther;
        var top = rank > rankOther ? rank : rankOther;
        
        $other.find('.numbars').removeClass('red green').addClass(otherColor);
        $this.find('.numbars').removeClass('red green').addClass(color)
        //    .data('top', top)
            .data('rank',rank);
        //$this.append('<div>'+top+','+rank+'</div>');
        
        $this.find('.numbars b').html(rank).css({'width': (rank / top * 100) +'%'});
        $other.find('.numbars b').css({'width': (rankOther / top * 100) +'%'});
        
    });
}

$(document).ready(function(){
    $('input[name=c1], input[name=c2]').trigger($.Event('keyup', {'keyCode':13}));
});