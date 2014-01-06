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
            var max = Math.pow(10, (Math.ceil(data.Rank)+'').length);
            var maxOther = Math.pow(10, (Math.ceil(parseInt($other.find('.numbars').data('rank')))+'').length);
            var rankOther = parseInt($other.find('.numbars').data('rank'));
            //console.log(rankOther, maxOther);
            max = max > maxOther ? max : maxOther;
            var color = data.Rank >= rankOther ? 'green' : 'red';
            var otherColor = color === 'red' ? 'green' : 'red';
            $other.find('.numbars').data('max', max);
            
            
            $other.find('.numbars').removeClass('red green').addClass(otherColor);
            var numbars = $this.find('.numbars').removeClass('red green').addClass(color).data('rank',data.Rank);
            //$this.append('<div>'+max+','+data.Rank+'</div>');
            
            numbars.find('b').html(data.Rank).css({'width': data.Rank / max * 100});
            $other.find('.numbars b').css({'width': rankOther / max * 100});
            //$this.append(numbars);
            //new Numbars(numbars[0], data.Rank);
            
        });
}

$(document).ready(function(){
    $('input[name=c1], input[name=c2]').trigger($.Event('keyup', {'keyCode':13}));
});