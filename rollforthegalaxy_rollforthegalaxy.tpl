{OVERALL_GAME_HEADER}


<div id="scout_panel" class="scout_panel whiteblock">

    <h3>{SCOUT_TITLE}</h3>

    <h4>{SCOUTED_DEV}</h4>
    <div id="scout_dev">
    </div>

    <h4>{SCOUTED_WORLD}</h4>
    <div id="scout_world">
    </div>

</div>


<!-- BEGIN player -->

<div id="tableau_panel_{PLAYER_ID}" class="whiteblock  tableau_panel">
    <h3 class="tableau_name" style="color:#{PLAYER_COLOR}">{TABLEAU_NAME}</h3>
    

    <div id="dicetable_{PLAYER_ID}" class="dicetable">
        <div id="dicerow_{PLAYER_ID}_1" class="dicerow">
            <div class="dicerow_header_placeholder"><div id="dicerow_header_{PLAYER_ID}_1" class="dicerow_header dicerow_header_1"></div><div id="dicerow_headercontent_{PLAYER_ID}_1"></div></div>
            <div id="dicerow_content_{PLAYER_ID}_1" class="dicerow_content"></div>
            <div id="dicerow_background_{PLAYER_ID}_1" class="dicerow_background"></div>
        </div>
        <div id="dicerow_{PLAYER_ID}_2" class="dicerow">
            <div class="dicerow_header_placeholder"><div id="dicerow_header_{PLAYER_ID}_2" class="dicerow_header dicerow_header_2"></div><div id="dicerow_headercontent_{PLAYER_ID}_2"></div></div>
            <div id="dicerow_content_{PLAYER_ID}_2" class="dicerow_content"></div>
            <div id="dicerow_background_{PLAYER_ID}_2" class="dicerow_background"></div>
        </div>
        <div id="dicerow_{PLAYER_ID}_3" class="dicerow">
            <div class="dicerow_header_placeholder"><div id="dicerow_header_{PLAYER_ID}_3" class="dicerow_header dicerow_header_3"></div><div id="dicerow_headercontent_{PLAYER_ID}_3"></div></div>
            <div id="dicerow_content_{PLAYER_ID}_3" class="dicerow_content"></div>
            <div id="dicerow_background_{PLAYER_ID}_3" class="dicerow_background"></div>
        </div>
        <div id="dicerow_{PLAYER_ID}_4" class="dicerow">
            <div class="dicerow_header_placeholder"><div id="dicerow_header_{PLAYER_ID}_4" class="dicerow_header dicerow_header_4"></div><div id="dicerow_headercontent_{PLAYER_ID}_4"></div></div>
            <div id="dicerow_content_{PLAYER_ID}_4" class="dicerow_content"></div>
            <div id="dicerow_background_{PLAYER_ID}_4" class="dicerow_background"></div>
        </div>
        <div id="dicerow_{PLAYER_ID}_5" class="dicerow">
            <div class="dicerow_header_placeholder"><div id="dicerow_header_{PLAYER_ID}_5" class="dicerow_header dicerow_header_5"></div><div id="dicerow_headercontent_{PLAYER_ID}_5"></div></div>
            <div id="dicerow_content_{PLAYER_ID}_5" class="dicerow_content"></div>
            <div id="dicerow_background_{PLAYER_ID}_5" class="dicerow_background"></div>
        </div>
    </div>

    <div id="board_{PLAYER_ID}" class="player_board">
        
        <div id="credit_{PLAYER_ID}_0" class="credit_space credit_space0"></div>
        <div id="credit_{PLAYER_ID}_1" class="credit_space credit_space1"></div>
        <div id="credit_{PLAYER_ID}_2" class="credit_space credit_space2"></div>
        <div id="credit_{PLAYER_ID}_3" class="credit_space credit_space3"></div>
        <div id="credit_{PLAYER_ID}_4" class="credit_space credit_space4"></div>
        <div id="credit_{PLAYER_ID}_5" class="credit_space credit_space5"></div>
        <div id="credit_{PLAYER_ID}_6" class="credit_space credit_space6"></div>
        <div id="credit_{PLAYER_ID}_7" class="credit_space credit_space7"></div>
        <div id="credit_{PLAYER_ID}_8" class="credit_space credit_space8"></div>
        <div id="credit_{PLAYER_ID}_9" class="credit_space credit_space9"></div>
        <div id="credit_{PLAYER_ID}_10" class="credit_space credit_space10"></div>
        <div id="credit_{PLAYER_ID}_selector" class="credit_selector"></div>
        
        <div id="cup_{PLAYER_ID}" class="cup">
        </div>
        <div id="citizenry_{PLAYER_ID}" class="citizenry">
        </div>

        <div id="dev_in_built_zone_{PLAYER_ID}" class="dev_in_built_zone">
            <div id="dev_in_built_{PLAYER_ID}" class="dev_in_built">
            </div>
            <div id="dev_dice_{PLAYER_ID}" class="dev_dice">
            </div>
            <div id="dev_in_built_zone_counter_{PLAYER_ID}" class="dev_in_built_zone_counter">x<span id="dev_in_built_counter_{PLAYER_ID}">0</span></div>
        </div>
        <div id="world_in_built_zone_{PLAYER_ID}" class="world_in_built_zone">
            <div id="world_in_built_{PLAYER_ID}" class="world_in_built">
            </div>
            <div id="world_dice_{PLAYER_ID}" class="world_dice">
            </div>
            <div id="world_in_built_zone_counter_{PLAYER_ID}" class="world_in_built_zone_counter">x<span id="world_in_built_counter_{PLAYER_ID}">0</span></div>
        </div>
                
    </div>
    
    <div class="clear"></div>


    <div id="tableau_{PLAYER_ID}" class="tableau">
    
    

    </div>
        
    <br class="clear" />
</div>
<!-- END player -->

<div id="roll_infos" class="whiteblock">
    {REMAINING_VP_CHIPS}: &nbsp;<img src="{GAMETHEMEURL}img/vp.svg" class="imgtext"/><b> x <span id="vp_stock">0</span></b>
    
    &nbsp;
    &nbsp;
    &nbsp;
    &nbsp;
    &nbsp;
    {TRADING_PRICES}: <div id="tradeprice"></div>
</div>



<div id="shipping_choice_panel">
    <div id="shipping_choice_trade">Trade for $</div>
    <div id="shipping_choice_consume">Consume for VP</div>    
</div>




<script type="text/javascript">

// Javascript HTML templates

var jstpl_player_board = '<div class="clear">\
        <div class="boardblock">\
            <span class="player_credit_tt">$ <span id="player_credit_${id}" class="player_credit">0</span></span>\
            &nbsp;&nbsp;&nbsp;\
            <span class="player_vp_tt"><img src="{GAMETHEMEURL}img/vp.svg" class="imgtext"/> x<span id="player_vp_${id}" class="player_vp">0</span></span>\
            &nbsp;&nbsp;&nbsp;\
            <span class="player_tableaucount_tt"><img src="{GAMETHEMEURL}img/cardback.png" class="imgtext tableaucount" /> x<span id="tableau_nbr_${id}" class="tableaucount">0</span></span>\
            <span id="player_options_btn_${id}" class="player_options_btn"><i class="fa fa-cog"></i></span>\
        </div>\
        <div id="player_options_panel_${id}" class="player_options_panel">\
            <div class="player_option_row">\
                <label><input type="checkbox" id="player_option_skip_recall_${id}" class="player_option_checkbox" /> Skip Recall</label>\
                <span id="player_option_skip_recall_info_${id}" class="player_option_info"><i class="fa fa-info-circle"></i></span>\
            </div>\
            <div class="player_option_row">\
                <label><input type="checkbox" id="player_option_prioritize_colored_${id}" class="player_option_checkbox" /> Prioritize Colored Dice</label>\
                <span id="player_option_prioritize_colored_info_${id}" class="player_option_info"><i class="fa fa-info-circle"></i></span>\
            </div>\
        </div>';

var jstpl_card_content = '<div class="card_content card_content_${type}" id="card_content_${id}"><div class="tile_title">${name}</div><div id="resourcezone_${id}" class="resourcezone"></div><div class="card_remove"></div></div>';

var jstpl_buildcard_content = '<div class="reorder_content">\
<div class="bgabutton bgabutton_blue" id="reorder_top_${id}"><i class="fa fa-arrow-down"></i></div>&nbsp;\
<div class="bgabutton bgabutton_blue" id="reorder_bot_${id}"><i class="fa fa-arrow-up"></i></div>&nbsp;\
<div class="bgabutton bgabutton_blue" id="reorder_fli_${id}"><i class="fa fa-repeat"></i></div>\
</div>';
/*
// Example:
var jstpl_some_game_item='<div class="my_game_item" id="my_game_item_${id}"></div>';

*/

</script>  

{OVERALL_GAME_FOOTER}
