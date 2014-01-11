var data;

var weights = JSON.parse(localStorage.getItem('weights')) || [1,10,100,1000,100,1000,1000,10,10,10,10,10,10,1000,0,0,1];

$('input[name=c1], input[name=c2]').on('keyup', function(e){
    if(e.keyCode == 13)
        get($(this).attr('name'), $(this).val());
});

function get(name, val){
    var $this = $('div.' + name);
    
    $this.find('.photo').css({
        'background-image':'url(\'img/loader.gif\')',
        'background-position':'center center',
        'background-size':'auto'
    });
    
    $.getJSON('./index.php?q=' + val, function(result) {
        
        data = result;
        
        $this.find('.photo').css({
            'background-image':'url(\''+data.Wikipedia.Photo+'\')',
            'background-position':'top center',
            'background-size':'cover'
        });
        var j = 0;
        for(var i in data){
            for(var k in data[i]){
                if($('tr.'+i+'.'+k).length){
                    $('tr.'+i+'.'+k+' input').val(weights[j++]);
                    $('tr.'+i+'.'+k+' b').html($('tr.'+i+'.'+k+' input').val());
                    $('tr.'+i+'.'+k+' .'+name).html(data[i][k]);
                }
            }
        }
        
        calculate();
        
    });
}

$('input[type=range]').on('change', function(e){
    $(this).next('b').html($(this).val());
});
$('input[type=range]').on('mouseup', function(e){
    calculate();
});

function calculate(){
    var rank = 0;
    var rankOther = 0;
    var j = 0;
    for(var i in data){
        for(var k in data[i]){
            if($('tr.'+i+'.'+k).length){
                weights[j] = $('tr.'+i+'.'+k+' input').val() *1;
                rank += $('tr.'+i+'.'+k+' .c1').html() * weights[j];
                rankOther +=$('tr.'+i+'.'+k+' .c2').html() * weights[j++];
            }
            
        }
    }
    localStorage.setItem('weights', JSON.stringify(weights));
    showResults(rank, rankOther);
}

function showResults(rank, rankOther){
    
    var $this = $('div.c1');
    var $other = $('div.c2');
    
    var color = rank > rankOther ? 'green' : 'red';
    var otherColor = color === 'red' ? 'green' : 'red';
    
    $other.find('.numbars').removeClass('red green').addClass(otherColor);
    $this.find('.numbars').removeClass('red green').addClass(color);
    
    var top = rank > rankOther ? rank : rankOther;
    $this.find('.numbars b').html(rank).css({'width': (rank / top * 100) +'%'});
    $other.find('.numbars b').html(rankOther).css({'width': (rankOther / top * 100) +'%'});
}

$(document).ready(function(){
    $('input[name=c1], input[name=c2]').trigger($.Event('keyup', {'keyCode':13}));
});