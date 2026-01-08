/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * RollForTheGalaxy implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * rollforthegalaxy.js
 *
 * RollForTheGalaxy user interface script
 *
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

define([
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/stock",
    "ebg/counter"
],
function (dojo, declare) {
    return declare("bgagame.rollforthegalaxy", ebg.core.gamegui, {
        constructor: function(){
            console.log('rollforthegalaxy constructor');

            // Here, you can init the global variables of your user interface
            // Example:
            // this.myGlobalValue = 0;

            this.playerTableau = {};
            this.tile_size = 160;
            this.dicePhases = {};   // player_id => phase_id => stock
            this.dicePhasesHeader = {};   // player_id => phase_id => stock

            this.cup = {};
            this.citizenry = {};
            this.devdice = {};
            this.worlddice = {};

            this.playerDevInBuilt = {};
            this.playerWorldInBuilt = {};
            this.worldResource = {};

            this.scoutedDev = null;
            this.scoutedWorld = null;
        },

        /*
            setup:

            This method must set up the game user interface according to current game situation specified
            in parameters.

            The method is called each time the game interface is displayed to a player, ie:
            _ when the game starts
            _ when a player refreshes the game page (F5)

            "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
        */

        setup: function( gamedatas )
        {
            console.log( "Starting game setup" );

            // Setting up player boards
            for( var player_id in gamedatas.players )
            {
                var player = gamedatas.players[player_id];


                this.playerTableau[ player_id ] = new ebg.stock();

                this.playerTableau[ player_id ].create( this, $('tableau_'+player_id), this.tile_size, this.tile_size );
                this.playerTableau[ player_id ].resizeItems( this.tile_size, this.tile_size, 10*this.tile_size, 16*this.tile_size );
                this.playerTableau[ player_id ].onItemCreate = dojo.hitch( this, 'setupNewCard' );
                this.playerTableau[ player_id ].selectionApparance = 'class';
                this.playerTableau[ player_id ].order_items = true;
                this.playerTableau[ player_id ].item_margin = 10;
                if( player_id == this.player_id )
                {
                    this.playerTableau[ player_id ].onChangeSelection = dojo.hitch( this, 'onTableauChangeSelection' );
                    this.playerTableau[ player_id ].setSelectionMode( 1 );
                }
                else
                {
                    this.playerTableau[ player_id ].setSelectionMode( 0 );
                }

                this.playerTableau[ player_id ].image_items_per_row = 10;
                this.playerTableau[ player_id ].autowidth = true;

                for( var i in gamedatas.tiles_types )
                {
                    this.playerTableau[ player_id ].addItemType( i, this.getTileWeight( i ), g_gamethemeurl+'img/tiles.jpg', this.getTileSpriteFromType( i )  );
                }


                this.playerDevInBuilt[ player_id ] = new ebg.stock();
                this.playerDevInBuilt[ player_id ].create( this, $('dev_in_built_'+player_id), this.tile_size, this.tile_size );
                this.playerDevInBuilt[ player_id ].resizeItems( this.tile_size, this.tile_size, 10*this.tile_size, 16*this.tile_size );
                this.playerDevInBuilt[ player_id ].image_items_per_row = 10;
                this.playerDevInBuilt[ player_id ].autowidth = false;
                this.playerDevInBuilt[ player_id ].item_margin = 0;
                this.playerDevInBuilt[ player_id ].setOverlap( 1, 1 );
                this.playerDevInBuilt[ player_id ].order_items = false;
                this.playerDevInBuilt[ player_id ].onItemCreate = dojo.hitch( this, 'setupNewCardBuild' );

                for( var i in gamedatas.tiles_types )
                {
                    this.playerDevInBuilt[ player_id ].addItemType( i, this.getTileWeight( i ), g_gamethemeurl+'img/tiles.jpg', this.getTileSpriteFromType( i )  );
                }


                this.playerWorldInBuilt[ player_id ] = new ebg.stock();
                this.playerWorldInBuilt[ player_id ].create( this, $('world_in_built_'+player_id), this.tile_size, this.tile_size );
                this.playerWorldInBuilt[ player_id ].resizeItems( this.tile_size, this.tile_size, 10*this.tile_size, 16*this.tile_size );
                this.playerWorldInBuilt[ player_id ].image_items_per_row = 10;
                this.playerWorldInBuilt[ player_id ].autowidth = false;
                this.playerWorldInBuilt[ player_id ].item_margin = 0;
                this.playerWorldInBuilt[ player_id ].setOverlap( 1, 1 );
                this.playerWorldInBuilt[ player_id ].order_items = false;
                this.playerWorldInBuilt[ player_id ].onItemCreate = dojo.hitch( this, 'setupNewCardBuild' );

                for( var i in gamedatas.tiles_types )
                {
                    this.playerWorldInBuilt[ player_id ].addItemType( i, this.getTileWeight( i ), g_gamethemeurl+'img/tiles.jpg', this.getTileSpriteFromType( i )  );
                }

                if( player_id == this.player_id )
                {
                    dojo.connect( $('dev_in_built_zone_'+player_id), 'mouseenter', this, 'onConstructionZoneMouseEnter' );
                    dojo.connect( $('dev_in_built_zone_'+player_id), 'mouseleave', this, 'onConstructionZoneMouseLeave' );
                    dojo.connect( $('world_in_built_zone_'+player_id), 'mouseenter', this, 'onConstructionZoneMouseEnter' );
                    dojo.connect( $('world_in_built_zone_'+player_id), 'mouseleave', this, 'onConstructionZoneMouseLeave' );
                }

                this.dicePhases[ player_id ]= {};
                this.dicePhasesHeader[ player_id ]= {};
                for( var phase=1; phase<=5; phase++ )
                {
                    this.dicePhases[ player_id ][ phase ] = new ebg.stock();
                    this.dicePhases[ player_id ][ phase ].create( this, $('dicerow_content_'+player_id+'_'+phase), 50, 50 );
                    this.dicePhases[ player_id ][ phase ].onItemCreate = dojo.hitch( this, 'setupNewPhaseDie' );
                    this.dicePhases[ player_id ][ phase ].image_items_per_row = 7;
                    this.dicePhases[ player_id ][ phase ].resizeItems( 50, 50, 7*50, 7*50 );
                    this.dicePhases[ player_id ][ phase ].setSelectionAppearance( 'class' );

                    this.dicePhasesHeader[ player_id ][ phase ] = new ebg.stock();
                    this.dicePhasesHeader[ player_id ][ phase ].create( this, $('dicerow_headercontent_'+player_id+'_'+phase), 50, 50 );
                    this.dicePhasesHeader[ player_id ][ phase ].onItemCreate = dojo.hitch( this, 'setupNewPhaseDie' );
                    this.dicePhasesHeader[ player_id ][ phase ].image_items_per_row = 7;
                    this.dicePhasesHeader[ player_id ][ phase ].resizeItems( 50, 50, 7*50, 7*50 );
                    this.dicePhasesHeader[ player_id ][ phase ].setSelectionAppearance( 'class' );

                    if( player_id == this.player_id )
                    {
                        this.dicePhases[ player_id ][ phase ].setSelectionMode( 1 );
                        this.dicePhases[ player_id ][ phase ].onChangeSelection = dojo.hitch( this, 'onDiceChangeSelection' );

                        this.dicePhasesHeader[ player_id ][ phase ].setSelectionMode( 1 );
                        this.dicePhasesHeader[ player_id ][ phase ].onChangeSelection = dojo.hitch( this, 'onDiceHeaderChangeSelection' );

                        dojo.connect( $('dicerow_background_'+player_id+'_'+phase), 'onclick', this, 'onPlaceDiceOnRow' );
                        dojo.connect( $('dicerow_header_'+player_id+'_'+phase), 'onclick', this, 'onPlaceDiceOnHeader' );
                    }
                    else
                    {
                        this.dicePhases[ player_id ][ phase ].setSelectionMode( 0 );
                        this.dicePhasesHeader[ player_id ][ phase ].setSelectionMode( 0 );
                    }

                    for( var die_type in gamedatas.dice_types )
                    {
                        for( var value=1;value<=7;value++ )
                        {
                            var dieface = 7*( die_type-1 ) + ( value - 1 );
                            this.dicePhases[ player_id ][ phase ].addItemType( dieface, dieface, g_gamethemeurl+'img/dice.png', dieface );
                            this.dicePhasesHeader[ player_id ][ phase ].addItemType( dieface, dieface, g_gamethemeurl+'img/dice.png', dieface );
                        }
                    }


                    this.cup[ player_id ] = new ebg.stock();
                    this.cup[ player_id ].create( this, $('cup_'+player_id ), 20, 20 );
                    this.cup[ player_id ].onItemCreate = dojo.hitch( this, 'setupNewDie' );
                    this.cup[ player_id ].image_items_per_row = 1;
                    this.cup[ player_id ].resizeItems( 20, 20, 1*20, 7*20 );
                    for( var i = 1; i<=7;i++ )
                    {
                        this.cup[ player_id ].addItemType( i, i, g_gamethemeurl+'img/minidice.png', i-1  );
                    }
                    if( player_id != this.player_id )
                    {
                        this.cup[ player_id ].setSelectionMode( 0 );
                    }
                    else
                    {
                        this.cup[ player_id ].onChangeSelection = dojo.hitch( this, 'onCupSelectionChange' );
                    }

                    this.citizenry[ player_id ] = new ebg.stock();
                    this.citizenry[ player_id ].create( this, $('citizenry_'+player_id ), 20, 20 );
                    this.citizenry[ player_id ].onItemCreate = dojo.hitch( this, 'setupNewDie' );
                    this.citizenry[ player_id ].image_items_per_row = 1;
                    if( player_id == this.player_id )
                    {
                        this.citizenry[ player_id ].onChangeSelection = dojo.hitch( this, 'onCitizenrySelectionChange' );
                    }
                    else
                    {
                        this.citizenry[ player_id ].setSelectionMode( 0 );
                    }
                    this.citizenry[ player_id ].resizeItems( 20, 20, 1*20, 7*20 );
                    for( var i = 1; i<=7;i++ )
                    {
                        this.citizenry[ player_id ].addItemType( i, i, g_gamethemeurl+'img/minidice.png', i-1  );
                    }


                    this.devdice[ player_id ] = new ebg.stock();
                    this.devdice[ player_id ].create( this, $('dev_dice_'+player_id ), 30, 30 );
                    this.devdice[ player_id ].onItemCreate = dojo.hitch( this, 'setupNewDie' );
                    this.devdice[ player_id ].image_items_per_row = 1;
                    this.devdice[ player_id ].resizeItems( 30, 30, 1*30, 7*30 );
                    for( var i = 1; i<=7;i++ )
                    {
                        this.devdice[ player_id ].addItemType( i, i, g_gamethemeurl+'img/minidice.png', i-1  );
                    }
                    if( player_id == this.player_id )
                    {
                        this.devdice[ player_id ].onChangeSelection = dojo.hitch( this, 'onDevDiceSelectionChange' );
                    }
                    else
                    {
                        this.devdice[ player_id ].setSelectionMode( 0 );
                    }

                    this.worlddice[ player_id ] = new ebg.stock();
                    this.worlddice[ player_id ].create( this, $('world_dice_'+player_id ), 30, 30 );
                    this.worlddice[ player_id ].onItemCreate = dojo.hitch( this, 'setupNewDie' );
                    this.worlddice[ player_id ].image_items_per_row = 1;
                    this.worlddice[ player_id ].resizeItems( 30, 30, 1*30, 7*30 );
                    for( var i = 1; i<=7;i++ )
                    {
                        this.worlddice[ player_id ].addItemType( i, i, g_gamethemeurl+'img/minidice.png', i-1  );
                    }
                    if( player_id == this.player_id )
                    {
                        this.worlddice[ player_id ].onChangeSelection = dojo.hitch( this, 'onWorldDiceSelectionChange' );
                    }
                    else
                    {
                        this.worlddice[ player_id ].setSelectionMode( 0 );
                    }

                }

                // Player board
                var player_board_div = $('player_board_'+player_id);
                dojo.place( this.format_block('jstpl_player_board', player ), player_board_div );

                this.updateCredit( player_id, player.credit );
                $('player_vp_'+player_id).innerHTML = player.vp_chip;

                if( player_id == this.player_id )
                {
                    this.scoutedDev = new ebg.stock();
                    this.scoutedDev.create( this, $('scout_dev'), this.tile_size, this.tile_size );
                    this.scoutedDev.resizeItems( this.tile_size, this.tile_size, 10*this.tile_size, 16*this.tile_size );
                    this.scoutedDev.onItemCreate = dojo.hitch( this, 'setupNewCardScouted' );
                    this.scoutedDev.onChangeSelection = dojo.hitch( this, 'onPickScoutedTile' );
                    this.scoutedDev.order_items = false;

                    this.scoutedDev.image_items_per_row = 10;
                    this.scoutedDev.autowidth = true;

                    for( var i in gamedatas.tiles_types )
                    {
                        this.scoutedDev.addItemType( i, this.getTileWeight( i ), g_gamethemeurl+'img/tiles.jpg', this.getTileSpriteFromType( i )  );
                    }

                    this.scoutedWorld = new ebg.stock();
                    this.scoutedWorld.create( this, $('scout_world'), this.tile_size, this.tile_size );
                    this.scoutedWorld.resizeItems( this.tile_size, this.tile_size, 10*this.tile_size, 16*this.tile_size );
                    this.scoutedWorld.onItemCreate = dojo.hitch( this, 'setupNewCardScouted' );
                    this.scoutedWorld.onChangeSelection = dojo.hitch( this, 'onPickScoutedTile' );
                    this.scoutedWorld.order_items = false;

                    this.scoutedWorld.image_items_per_row = 10;
                    this.scoutedWorld.autowidth = true;

                    for( var i in gamedatas.tiles_types )
                    {
                        this.scoutedWorld.addItemType( i, this.getTileWeight( i ), g_gamethemeurl+'img/tiles.jpg', this.getTileSpriteFromType( i )  );
                    }
                }
            }


            // Dice in cup
            for( var i in gamedatas.dicecup )
            {
                var die = gamedatas.dicecup[i];

                this.cup[ die.location_arg ].addToStockWithId( die.type, die.id );
            }

            // Dice in citizenry
            for( var i in gamedatas.dicecitizenry )
            {
                var die = gamedatas.dicecitizenry[i];

                this.citizenry[ die.location_arg ].addToStockWithId( die.type, die.id );
            }

            // Tiles in dev construction zone
            for( var i in gamedatas.builddev )
            {
                var tile = gamedatas.builddev[i];
                var player_id = tile.location.substr( 2 );

                this.playerDevInBuilt[ player_id ].addToStockWithId( tile.type, tile.id );
                $('dev_in_built_counter_'+player_id).innerHTML = toint( $('dev_in_built_counter_'+player_id).innerHTML ) +1;
            }

            // Tiles in world construction zone
            for( var i in gamedatas.buildworld )
            {
                var tile = gamedatas.buildworld[i];
                var player_id = tile.location.substr( 2 );

                this.playerWorldInBuilt[ player_id ].addToStockWithId( tile.type, tile.id );
                $('world_in_built_counter_'+player_id).innerHTML = toint( $('world_in_built_counter_'+player_id).innerHTML ) +1;
            }

            // Dice on construction zones
            for( var i in gamedatas.devdice )
            {
                var die = gamedatas.devdice[i];
                this.devdice[ die.location_arg ].addToStockWithId( die.type, die.id );
            }
            for( var i in gamedatas.worlddice )
            {
                var die = gamedatas.worlddice[i];
                this.worlddice[ die.location_arg ].addToStockWithId( die.type, die.id );
            }

            // Tiles in tableau
            for( var i in gamedatas.tableau )
            {
                var tile = gamedatas.tableau[i];
                var player_id = tile.location_arg;

                this.playerTableau[ player_id ].addToStockWithId( tile.type, tile.id );

                $('tableau_nbr_'+player_id).innerHTML = toint( $('tableau_nbr_'+player_id).innerHTML )+1;

                if( tile.type == 32 && this.player_id == player_id )
                {
                    dojo.addClass( 'tableau_panel_'+this.player_id, 'tableau_with_al' );
                }
            }

            // Tiles in scouted area
            for( var i in gamedatas.scouted.dev )
            {
                var tile = gamedatas.scouted.dev[i];

                this.scoutedDev.addToStockWithId( tile.type, tile.id );
                this.showScoutPanel();
            }
            for( var i in gamedatas.scouted.world )
            {
                var tile = gamedatas.scouted.world[i];

                this.scoutedWorld.addToStockWithId( tile.type, tile.id );
                this.showScoutPanel();
            }

            // Resources
            for( var i in gamedatas.resources )
            {
                var ress = gamedatas.resources[i];
                this.worldResource[ ress.location_arg ].addToStockWithId( ress.type, ress.id );
            }

            // Tooltips
            this.addTooltipToClass( 'player_credit_tt', _("Credits: with credits you can recruit worker (dice) from your citizenry to your cup."), '' );
            this.addTooltipToClass( 'player_vp_tt', _("Victory points chips token."), '' );
            this.addTooltipToClass( 'player_tableaucount_tt', _("Number of cards in tableau (12 cards or more triggers game end)."), '' );

            // Phases
            this.updatePhases( gamedatas.selectedphases );

//            dojo.connect( $('testbtn'), 'onclick', this, 'onTest' );

            if( $('tableau_panel_'+this.player_id) )
            {
                dojo.place( $('roll_infos'), 'tableau_panel_'+this.player_id, 'after' );
            }
            else
            {
                dojo.place( $('roll_infos'), 'scout_panel', 'after' );
            }

            $('vp_stock').innerHTML = gamedatas.vp_stock;

            this.addTooltipHtmlToClass( 'dicerow_header_1', '<h3>'+_("Explore")+'</h3><hr/>'+_("Scout for new tiles or Stock to gain Galactic credits.") );
            this.addTooltipHtmlToClass( 'dicerow_header_2', '<h3>'+_("Develop")+'</h3><hr/>'+_("Develop the topmost development on your construction stack.") );
            this.addTooltipHtmlToClass( 'dicerow_header_3', '<h3>'+_("Settle")+'</h3><hr/>'+_("Settle the topmost world on your construction stack.") );
            this.addTooltipHtmlToClass( 'dicerow_header_4', '<h3>'+_("Produce")+'</h3><hr/>'+_("Produce a good in a non-gray world of your tableau.") );
            this.addTooltipHtmlToClass( 'dicerow_header_5', '<h3>'+_("Ship")+'</h3><hr/>'+_("Trade a good to gain 3-6 Credits or Consume a good to gain 1-3 Victory points.") );


            this.updatePhaseDice( gamedatas.dicephase, gamedatas.players );

            dojo.connect( $('shipping_choice_consume'), 'onclick', this, 'onShipConsume' );
            dojo.connect( $('shipping_choice_trade'), 'onclick', this, 'onShipTrade' );
            dojo.connect( $('shipping_choice_panel'), 'onclick', this, 'onShipCancel' );


            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();

            console.log( "Ending game setup" );
        },

        getTileSpriteFromType: function( type )
        {
            if( type <= 56 )
            {   return type-1;    }
            else if( type <= 168 )
            {   return 70 + (type-100); }
            else if( type <= 1018 ) // Starting tiles
            {   return 140 + (type-1000)-1; }
        },

        getTileWeight: function( type )
        {
            if( type >= 1000 )
            {
                return ( type - 2000 ); // Starting double tiles, alway first
            }
            else if( type >= 160 )
            {
                return (type - 200);    // Starting world, always second
            }
            else
            {
                return 1; // All have the same weight
            }
        },

        onTest: function( evt )
        {
            dojo.stopEvent( evt );

            dojo.query( '#testdie .bgadie_platform' ).addClass( 'roll' );

            setTimeout( dojo.hitch( this, 'onStopDie'), 750 );

        },

        onStopDie: function()
        {
            dojo.query( '#testdie .bgadie_platform' ).removeClass( 'roll' );
            dojo.query( '#testdie .bgadie_dice' ).removeClass( 'value1 value2 value3 value4 value5 value6' );

            var value = Math.floor( Math.random() * 6 +1 );
            dojo.query( '#testdie .bgadie_dice' ).addClass( 'value'+value );

            console.log( value );
        },

        onConstructionZoneMouseEnter: function( evt )
        {
            if( this.gamedatas.gamestate.name == 'savedie' )
            {   return; }   // Do not expand on this state so we can select die to save

            if( evt.currentTarget.id == 'dev_in_built_zone_'+this.player_id )
            {
                this.playerDevInBuilt[ this.player_id ].item_margin = -50;
                this.playerDevInBuilt[ this.player_id ].setOverlap( 0, 0 );
                this.playerDevInBuilt[ this.player_id ].updateDisplay();
        //        dojo.style( 'dev_dice_'+this.player_id, 'display', 'none' );  // Otherwise, the mouse is going on it and trigger onmouseleave
            }
            else
            {
                this.playerWorldInBuilt[ this.player_id ].item_margin = -50;
                this.playerWorldInBuilt[ this.player_id ].setOverlap( 0, 0 );
                this.playerWorldInBuilt[ this.player_id ].updateDisplay();
        //        dojo.style( 'world_dice_'+this.player_id, 'display', 'none' );  // Otherwise, the mouse is going on it and trigger onmouseleave
            }
        },

        onConstructionZoneMouseLeave: function( evt )
        {
            if( evt.currentTarget.id == 'dev_in_built_zone_'+this.player_id )
            {
                this.playerDevInBuilt[ this.player_id ].item_margin = 0;
                this.playerDevInBuilt[ this.player_id ].setOverlap( 1, 1 );
                this.playerDevInBuilt[ this.player_id ].updateDisplay();
                dojo.style( 'dev_dice_'+this.player_id, 'display', 'block' );
            }
            else
            {
                this.playerWorldInBuilt[ this.player_id ].item_margin = 0;
                this.playerWorldInBuilt[ this.player_id ].setOverlap( 1, 1 );
                this.playerWorldInBuilt[ this.player_id ].updateDisplay();
                dojo.style( 'world_dice_'+this.player_id, 'display', 'block' );
            }
        },

        ///////////////////////////////////////////////////
        //// Game & client states

        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function( stateName, args )
        {
            console.log( 'Entering state: '+stateName );

            switch( stateName )
            {

            case 'explore':

                if( this.scoutedDev !== null )
                {
                    if( this.scoutedDev.count() + this.scoutedWorld.count() > 0 )
                    {
                        this.showScoutPanel();
                    }
                }
                break;

            /* Example:

            case 'myGameState':

                // Show some HTML block at this game state
                dojo.style( 'my_html_block_id', 'display', 'block' );

                break;
           */


            case 'dummmy':
                break;
            }
        },

        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function( stateName )
        {
            console.log( 'Leaving state: '+stateName );

            switch( stateName )
            {
            case 'assign':
                dojo.query( '.reassign_available' ).removeClass( 'reassign_available' );
                break ;

            /* Example:

            case 'myGameState':

                // Hide the HTML block we are displaying only during this game state
                dojo.style( 'my_html_block_id', 'display', 'none' );

                break;
           */


            case 'dummmy':
                break;
            }
        },

        // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
        //                        action status bar (ie: the HTML links in the status bar).
        //
        onUpdateActionButtons: function( stateName, args )
        {
            console.log( 'onUpdateActionButtons: '+stateName );

            if( this.isCurrentPlayerActive() )
            {
                switch( stateName )
                {
                 case 'startingWorldCombination':
                    this.addActionButton( 'done_choosecomboSwitch', _('Flip tiles'), 'onChooseComboFlip' );
                    this.addActionButton( 'done_choosecombo', _('Done'), 'onDoneChooseCombo' );
                    break;

                 case 'assign':
                    this.addActionButton( 'dictate', _('Dictate'), 'onDictate' );
                    if( args.dictate[ this.player_id ] != 0 )
                    {
                        dojo.style( 'dictate', 'display', 'none' );
                    }
                    this.addActionButton( 'reset_assign', _('Reset dice'), 'onResetAssign' );
                    this.addActionButton( 'done_assign', _('Done'), 'onDoneAssign' );

                    this.addTooltip( 'dictate', '', _("Select a die from any phase and clicks on Dictate to return it to the cup. After this, you can move any die to any phase.") );
                    this.addTooltip( 'reset_assign', '', _("Undo all your phase/dice assignment.") );

                    this.updateAvailableAssignPowers( args.assign );

                    break;

                 case 'explore':
                    this.addActionButton( 'scout', _('Scout (new tiles)'), 'onScout' );
                    this.addActionButton( 'scoutdiscard', _('Discard selected tiles'), 'onScoutDiscard' );
                    if (this.hasAlienArchaeology(this.player_id)) {
                        this.addActionButton( 'stock', _('Stock (+2$/4$)'), 'onStock' );
                    } else {
                        this.addActionButton( 'stock', _('Stock (+2$)'), 'onStock' );
                    }
                    break;

                 case 'manage':
                    this.addActionButton( 'manage', _('Done'), 'onManageDone' );
                    break;

                 case 'savedie':
                    this.addActionButton( 'cancel', _('Do not use'), 'onDoNotUse' );
                    break;


                }
            }
            else
            {
                if( stateName == 'assign' )
                {
                    this.addActionButton( 'reset_assign', _('Reset dice'), 'onResetAssign' );
                }
            }
        },

        updateAvailableAssignPowers: function( powers )
        {
            dojo.query( '.available_assign' ).removeClass( 'reassign_available' );

            for( var i in powers )
            {
                var power = powers[i];

                if( power.pid == this.player_id )
                {
                    dojo.addClass( 'tableau_'+this.player_id+'_item_'+power.id, 'reassign_available' );
                }
            }
        },

        ///////////////////////////////////////////////////
        //// Utility methods

        /*

            Here, you can defines some utility methods that you can use everywhere in your javascript
            script.

        */

        hasAlienArchaeology: function( player_id )
        {
            for (const val of Object.values(this.gamedatas.tableau)) {
                if (val.type == "1003" && val.location_arg == player_id) {
                    return true;
                }
            }
            return false;
        },

        setupNewCard: function( card_div, card_type_id, card_id )
        {
            if( card_type_id != 0 )
            {
                var card = this.gamedatas.tiles_types[ card_type_id ];
                var html = this.getCardTooltip( card_type_id, false );

                this.addTooltipHtml( card_div.id, html, 100 );

                var parts = card_id.split( '_' );
                var card_numeric_id = parts[ parts.length-1 ];

                dojo.place( this.format_block( 'jstpl_card_content', {
                                id:card_id,
                                numid: card_numeric_id,
                                type: card_type_id,
                                name: _(card.name)
                           } ), card_div.id );


                this.worldResource[ card_numeric_id ] = new ebg.stock();
                this.worldResource[ card_numeric_id ].create( this, $('resourcezone_'+card_id), 50, 50 );
                this.worldResource[ card_numeric_id ].onItemCreate = dojo.hitch( this, 'setupNewDie' );
                this.worldResource[ card_numeric_id ].image_items_per_row = 1;

                if( dojo.query( '#tableau_panel_'+this.player_id+' #'+card_id ).length > 0 )
                {
                    this.worldResource[ card_numeric_id ].onChangeSelection = dojo.hitch( this, 'onResourceClick' );
                }
                else
                {
                    this.worldResource[ card_numeric_id ].setSelectionMode(0);
                }
                this.worldResource[ card_numeric_id ].resizeItems( 50, 50, 1*50, 7*50 );
                for( var i = 1; i<=7;i++ )
                {
                    this.worldResource[ card_numeric_id ].addItemType( i, i, g_gamethemeurl+'img/minidice.png', i-1  );
                }

                dojo.addClass( card_div, 'roll_tile' );

                if( card_type_id == 1002 || card_type_id == 1004 || card_type_id == 1006 || card_type_id == 1008 || card_type_id == 1010
                  || card_type_id == 1012 || card_type_id == 1014 || card_type_id == 1016 || card_type_id == 1018 )
                {
                    dojo.addClass( card_div, 'faction_right_tile' );
                }
                if( card_type_id == 1001 || card_type_id == 1003 || card_type_id == 1005 || card_type_id == 1007 || card_type_id == 1009
                  || card_type_id == 1011 || card_type_id == 1013 || card_type_id == 1015 || card_type_id == 1017 )
                {
                    dojo.addClass( card_div, 'faction_left_tile' );
                }


                /// ONLY FOR DEV /////
                // TODO : to be removed ////

          /*      dojo.connect( card_div, 'dblclick', this, function( evt ) {

                    this.ajaxcall( "/rollforthegalaxy/rollforthegalaxy/debugeffect.html", {
                                                                            lock: true,
                                                                            id: card_numeric_id
                                                                         },
                                 this, function( result ) {}, function( is_error) {} );


                } );*/

                /////
            }
        },

        setupNewPhaseDie: function( card_div, card_type_id, card_id )
        {
            this.setupNewDie( card_div, Math.floor( card_type_id/7 )+1, card_id );
        },

        setupNewDie: function( card_div, card_type_id, card_id )
        {
            if( card_type_id != 0 )
            {
                var type = this.gamedatas.dice_types[ card_type_id ];

                html = '<h3>Die: '+type.name+'</h3>';
                html += '<hr/>';

                for( var i in type.faces )
                {
                    var face = type.faces[i];

                    if( face != 7 )
                    {
                        var backx = 100*(face-1);
                        var backy = 100*(card_type_id-1);
                        html+= '<div class="die_face_tooltip" style="background-position: -'+backx+'% -'+backy+'%"></div>';
                    }

                }

                this.addTooltipHtml( card_div.id, html, 100 );
            }
        },

        setupNewCardScouted:  function( card_div, card_type_id, card_id )
        {
            this.setupNewCard( card_div, card_type_id, card_id );

            dojo.connect( card_div, 'mouseenter', this, 'onEnterScoutedCard' );
            dojo.connect( card_div, 'mouseleave', this, 'onLeaveScoutedCard' );
        },

        setupNewCardBuild:  function( card_div, card_type_id, card_id )
        {
            this.setupNewCard( card_div, card_type_id, card_id );

            var parts = card_id.split( '_' );
            var card_numeric_id = parts[ parts.length-1 ];

            var card_content_id = null;
            if( $('card_content_dev_in_built_'+this.player_id+'_item_'+card_numeric_id ) )
            {
                card_content_id = 'card_content_dev_in_built_'+this.player_id+'_item_'+card_numeric_id;
            }
            else if( $('card_content_world_in_built_'+this.player_id+'_item_'+card_numeric_id ) )
            {
                card_content_id = 'card_content_world_in_built_'+this.player_id+'_item_'+card_numeric_id;
            }
            else
            {
                return ;
            }

            dojo.place( this.format_block( 'jstpl_buildcard_content', {
                        id: card_numeric_id
                       } ), card_content_id );

            this.addTooltip( 'reorder_top_'+card_numeric_id, '', _('Advanced Logistics: put this tile on top of the stack.') );
            this.addTooltip( 'reorder_bot_'+card_numeric_id, '', _('Advanced Logistics: put this tile at the bottom of the stack.') );
            this.addTooltip( 'reorder_fli_'+card_numeric_id, '', _('Advanced Logistics: flip this tile.') );

            dojo.connect( $('reorder_top_'+card_numeric_id), 'onclick', this, 'onAlMoveToTop' );
            dojo.connect( $('reorder_bot_'+card_numeric_id), 'onclick', this, 'onAlMoveToBot' );
            dojo.connect( $('reorder_fli_'+card_numeric_id), 'onclick', this, 'onAlFlip' );
        },

        onAlMoveToTop: function( evt )
        {
            this.checkAction( 'scout' );

            var tile_id = evt.currentTarget.id.substr( 12 );
            this.ajaxcall( "/rollforthegalaxy/rollforthegalaxy/advancedlogistics.html", {
                                                                    lock: true,
                                                                    tile: tile_id,
                                                                    al: 'top'
                                                                 },
                         this, function( result ) {}, function( is_error) {} );
        },
        onAlMoveToBot: function( evt )
        {
            this.checkAction( 'scout' );

            var tile_id = evt.currentTarget.id.substr( 12 );
            this.ajaxcall( "/rollforthegalaxy/rollforthegalaxy/advancedlogistics.html", {
                                                                    lock: true,
                                                                    tile: tile_id,
                                                                    al: 'bot'
                                                                 },
                         this, function( result ) {}, function( is_error) {} );
        },
        onAlFlip: function( evt )
        {
            this.checkAction( 'scout' );

            var tile_id = evt.currentTarget.id.substr( 12 );
            this.ajaxcall( "/rollforthegalaxy/rollforthegalaxy/advancedlogistics.html", {
                                                                    lock: true,
                                                                    tile: tile_id,
                                                                    al: 'flip'
                                                                 },
                         this, function( result ) {}, function( is_error) {} );
        },

        onEnterScoutedCard: function( evt )
        {
            var parts = evt.currentTarget.id.split('_');

            var category = parts[1];
            var tile_id = parts[3];

            if( category == 'dev' )
            {
                // Must signal that corresponding world is going to be removed
                dojo.addClass( 'scout_world_item_'+tile_id, 'to_be_removed' );
            }
            else
            {
                dojo.addClass( 'scout_dev_item_'+tile_id, 'to_be_removed' );
            }
        },
        onLeaveScoutedCard: function( evt )
        {
            var parts = evt.currentTarget.id.split('_');

            var category = parts[1];
            var tile_id = parts[3];

            if( category == 'dev' )
            {
                dojo.removeClass( 'scout_world_item_'+tile_id, 'to_be_removed' );
            }
            else
            {
                dojo.removeClass( 'scout_dev_item_'+tile_id, 'to_be_removed' );
            }
        },

        getCardTooltip: function( card_type_id )
        {
            var card = this.gamedatas.tiles_types[ card_type_id ];

            var html = '';

            html += '<h3>'+card.name+'</h3>';
            html += '<hr/>';

            if( card.category == 'dev' )
            {
                html += '<p>'+_("Development")+'</p>';
            }
            else
            {
                html += '<p>'+_("World")+'</p>';
            }

            html += '<p>'+_('Cost')+': '+card.cost+'</p>';

            html += '<hr/>';

            if( card.category == 'world' && card.type != 0 )
            {
                html += '<p>'+_("This world resources trading price:")+' $'+( card.type +2 )+'</p>';
            }

            // Effects description
            for( var i in card.powers )
            {
                var power = card.powers[i];
                var descr = '';

                if( power.power == 'gaindie' ) // OK
                {
                    var args = {};

                    args.type = this.gamedatas.dice_types[ power.type ].name;
                    if( typeof power.target == 'undefined' ||  power.target == 'citizenry' )
                    {   args.target = _('citizenry'); }
                    else if( power.target == 'cup' )
                    {   args.target = _('cup');   }

                    if( typeof power.nbr == 'undefined' || power.nbr == 1 )
                    {
                        descr += dojo.string.substitute( _("Add one ${type} die to your ${target}."), args );
                    }
                    else
                    {
                        args.nbr = power.nbr;
                        descr += dojo.string.substitute( _("Add ${nbr} ${type} dice to your ${target}."), args );
                    }

                }
                else if( power.power == 'removedie' ) // OK
                {
                    descr += _("Remove any one of your die.");
                }
                else if( power.power == 'trade_may_spend_for_vp' ) // OK
                {
                    descr += _("*Ship*: Each time you trade a good, you may spend $1 to gain 1 VP chip.");
                }
                else if( power.power == 'credit' ) //  OK
                {
                    if( typeof power.phase != 'undefined' )
                    {
                        descr += dojo.string.substitute( _("*${phase}*: +${nbr}$."), { nbr: power.nbr, phase: this.gamedatas.dice_faces[ power.phase ]  } );
                    }
                    else
                    {
                        descr += dojo.string.substitute( _("Gain ${nbr}$."), { nbr: power.nbr } );
                    }
                }
                else if( power.power == 'gaingood' ) // OK
                {
                    descr += dojo.string.substitute( _("Gain a ${good} on this world when you place it."), { good: this.gamedatas.colors[ card.type ] } );
                }
                else if( power.power == 'credit_when_build' ) // OK
                {
                    if( typeof power.option != 'undefined' )
                    {
                        if(power.option == 'world_only_plus_brown' )
                        {
                            descr += _('+1$ after completing any world. +1$ additional if this is a Rare elements (brown) world.');
                        }
                        else if( power.option == 'dev_only' )
                        {
                            descr += _('+1$ after completing each development, not including this one.');
                        }
                    }
                    else
                    {
                        descr += _('+1$ after completing each development/settle, not including this one.');
                    }

                }
                else if( power.power == 'credit_if_high_cost' ) // OK
                {
                    descr += _('*Ship*: +$1 if you have the highest-cost world(s) across all tableaus.')
                }
                else if( power.power == 'trade_bonus' ) // OK
                {
                    if( typeof power.good != 'undefined' )
                    {
                        descr += dojo.string.substitute( _('*Ship*: +$${bonus} for each good you trade from a ${type} world.'), { bonus: power.bonus, type: this.gamedatas.colors[ power.good ] } );
                    }
                    else
                    {
                        descr += dojo.string.substitute( _('*Ship*: +$${bonus} for each good you trade.'), { bonus: power.bonus } );
                    }
                }
                else if( power.power == 'reassign' ) // OK
                {
                    // from / to (ou 'current') / color (ou nonwhite) (ou tableau) / nbr

                    var args = {};

                    if( typeof power.nbr != 'undefined' )
                    {   args.nbr = power.nbr;   }
                    else
                    {   args.nbr = 1;   }

                    if( args.nbr == 1 )
                    {
                        descr += dojo.string.substitute( _('You may reassign one die'), args );
                    }
                    else
                    {
                        if( typeof power.option != 'undefined' )
                        {
                            descr += dojo.string.substitute( _('You may reassign exactly two same-phase dice'), args );
                        }
                        else
                        {
                            descr += dojo.string.substitute( _('You may reassign ${nbr} dice'), args );
                        }
                    }

                    if( typeof power.color != 'undefined' )
                    {
                        if( typeof power.color == 'array' || typeof power.color == 'object' )
                        {
                            var die_type_list = '';
                            for( var c in power.color )
                            {
                                if( die_type_list != '' )
                                {   die_type_list += ' '+_('or')+' ';  }

                                die_type_list += this.gamedatas.dice_types[ power.color[c] ].name;
                            }
                            descr += ' (' + die_type_list+')';
                        }
                        else if( power.color == 'nonwhite' )
                        {
                            descr += ' ('+_('non-home')+')';
                        }
                    }

                    if( typeof power.from != 'undefined' )
                    {
                        descr += ' '+_('from Explorer phase');  // Note: we only have this for now
                    }

                    if( typeof power.to != 'undefined' )
                    {
                        if( typeof power.to == 'string' )
                        {
                            // current
                            descr += ' '+_('to the phase you select');
                        }
                        else
                        {
                            // This is a table
                            descr += ' '+_('to')+' ';
                            var to_list = '';

                            for( p in power.to )
                            {
                                if( to_list != '' )
                                {
                                    to_list += _(' or ');
                                }

                                var phase_id = power.to[p];

                                to_list += this.gamedatas.dice_faces[ phase_id ]+' '+_('phase');
                            }

                            descr += to_list;
                        }
                    }

                    if( typeof power.if_most != 'undefined' )
                    {
                        descr = _('If you have the most Novelty (cyan) worlds in tableau, you may Reassign one or two workers to any phase(s). (If tied, you may Reassign one.)')

                    }

                    descr += '.';
                }
                else if( power.power == 'explore_reassign' ) // OK
                {
                    descr += _('*Explore*: You may rearrange all tiles in your construction zone (including turning them over).');
                }
                else if( power.power == 'vp_on_phase' ) // OK
                {
                    descr += _('*Ship*: 1 VP chip.')
                }
                else if( power.power == 'tmp_die' ) // OK
                {
                    if( power.phase == 5 )
                    {
                        if( power.type.length == 2 )
                        {
                            descr += _('*Ship*: Act as if you have an extra Home (white) shipper and an extra Genes (green) shipper for use this phase.');
                        }
                        else
                        {
                            descr += _('*Ship*: Act if you have one more Home (white) shipper for use this turn.');
                        }
                    }
                    else
                    {
                        if( power.type.length == 2 )
                        {
                            descr += _('*Explore*: Act as if you have an extra Home (white) explorer and an extra Alien Technology (yellow) explorer for use this phase.');
                        }
                        else
                        {
                            descr += _('*Explore*: Act as if you have an extra Home (white) explorer explorer for use this phase.');
                        }
                    }
                }
                else if( power.power == 'credit_if_most' ) // OK
                {
                    descr += _('*Explore*, *Produce*: +$1 if you have the most developments in tableau. (If tied for most you do not get anything.)');
                }
                else if( power.power == 'back_dice_on_settle' ) // OK
                {
                    if( power.nbr == 'all' )
                    {
                        descr += _('*Settle*: When completing each world, put all of the Military (red) settlers used into your cup instead of your Citizenry.');
                    }
                    else
                    {
                        descr += _('*Settle*: When completing each world, put one or two of the settlers used into your cup instead of your Citizenry.');
                    }
                }
                else if( power.power == 'back_dice_on_dev' ) // OK
                {
                    descr += _('*Develop*: When completing each development (after this one), put one or two of the developers used into your cup instead of your Citizenry.');
                }
                else if( power.power == 'credit_for_good' ) // OK
                {
                    if( power.phase == 4 )
                    {
                        descr += _('*Produce*'); // OK
                    }
                    else
                    {
                        descr += _('*Develop*');    // OK
                    }

                    if( typeof power.dice != 'undefined' )
                    {
                        descr += ': '+ dojo.string.substitute( _('+2$ for each good represented by a ${type} die at the end of this phase.'), { type: this.gamedatas.dice_types[ power.dice ].name }  );
                    }
                    else
                    {
                        descr += ': '+ dojo.string.substitute( _('+1$ for each good on ${type} world at the end of this phase.'), { type: this.gamedatas.colors[ power.good ] }  );
                    }

                }
                else if( power.power == 'credit_for_die' ) // OK
                {
                    descr += _('*Develop*');    // OK
                    descr += ': '+ dojo.string.substitute( _('+1$ for each ${type} die in your Citizenry at the end of this phase.'), { type: this.gamedatas.dice_types[ power.die ].name }  );
                }
                else if( power.power == 'consume_bonus' ) // OK
                {
                    descr += dojo.string.substitute( _('*Ship*: +${nbr}$ for each good you consume this phase'), power );

                    if( typeof power.from != 'undefined' )
                    {
                        descr += ' ('+dojo.string.substitute( _('from ${type} worlds'), {type: this.gamedatas.colors[ power.from ]} )+')';
                    }

                    descr += '.';
                }
                else if( power.power == 'consume_bonus_vp' ) // OK
                {
                    if( typeof power.from != 'undefined' )
                    {
                        if( power.from.length == 2 )
                        {
                            descr += dojo.string.substitute( _('*Ship*: +${nbr} VP chip for each good you consume this phase from a Genes (green) or Alien technology (yellow) world.'), power );
                        }
                        else
                        {
                            descr += dojo.string.substitute( _('*Ship*: +${nbr} VP chip for each good you consume this phase from a Rare Elements (brown) world.'), power );
                        }
                    }
                    else
                    {
                        descr += dojo.string.substitute( _('*Ship*: +${nbr} VP chip for each good you consume this phase.'), power );
                    }
                }
                else if( power.power == 'dev_discount' ) // OK
                {
                    if( typeof power.option != 'undefined' && power.option == 'only_reassign_powers' )
                    {
                        descr += _('*Develop*: All Reassign-power developments require one fewer developer to complete (but no fewer than one).');
                    }
                    else
                    {
                        descr += _('*Develop*: All developments (after this one) require one fewer developer to complete (but not fewer than one).');
                    }
                }
                else if( power.power == 'settle_discount' )// OK
                {
                    if( typeof power.option != 'undefined' && power.option == 'only_for_gray_doubled' )
                    {
                        descr += _('*Settle*: Grey worlds require two fewer settlers to complete (but no fewer than two).');
                    }
                    else if( typeof power.option != 'undefined' && power.option == 'only_for_green_yellow' )
                    {
                        descr += _('*Settle*: Genes (green) and Alien Technology (yellow) worlds require one fewer settler to complete.');
                    }
                    else
                    {
                        descr += _('*Settle*: All worlds require one fewer settler to complete (but not fewer than one).');
                    }
                }
                else if( power.power == 'extragood' ) // OK
                {
                    descr += _('*Produce*: each of your worlds can hold an extra good. (Each good still requires 1 shipper.)');
                }
                else if( power.power == 'explorekeep' ) // OK
                {
                    descr += _('*Explore*: Draw and keep an extra tile at the end of this phase. If you Scouted with at least one Alien Technology (yellow) explorer, +$1.');
                }
                else if( power.power == 'dictate' ) // OK
                {
                    descr += _('In the same round, you may Dictate more than once to Reassign workers.');
                }
                else if( power.power == 'three_on_dictate' ) // OK
                {
                    descr += _('When using Dictate, you may Reassign up to three (not one) other workers to any phase(s).');
                }
                else if( power.power == 'credit_on_gamestart' ) // OK
                {
                    descr += _('Begin the game with $8 instead of $1.');
                }
                else if( power.power == 'ship_bonus_per_twomilitary' ) // OK
                {
                    descr += _('*Ship*: +$1 for every two Military (red) dice (rounded up) in your Citizenry at the end of the phase.');
                }
                else if( power.power == 'explore_bonus_doubled' ) // OK
                {
                    descr += _('*Explore*: +$4 (instead of +$2) when Stocking with an Alien Technology (yellow) explorer.');
                }
                else if( power.power == 'explore_may_place_on_top' ) // OK
                {
                    descr += _('*Explore*: You may place new tiles on top of your stacks when Scouting.');
                }

                descr = descr.replace( /\*(.*?)\*/g, '<b>$1</b>' );

                html += '<p>'+descr+'</p>';

            }

            if( card.category == 'dev' && card.cost == 6 )
            {
                // VP at the end of the game

                html += '<hr/>';
                html += '<b>'+_("During final scoring")+'</b>: ';

                if( card.name == 'Galactic Federation' )
                {
                    html += _('Add one-third of your total base development cost (rounded up).');
                }
                else if( card.name == 'Galactic Exchange' )
                {
                    html += _('+1 VP for each different color of dice you own.');
                }
                else if( card.name == 'New Galactic Order' )
                {
                    html += _('+2 VP per 3 Military (red) dice you own (rounded up).');
                }
                else if( card.name == 'Mining League' )
                {
                    html += _('+2 VP per Rare Elements (brown) world in your tableau.');
                }
                else if( card.name == 'Free Trade Association' )
                {
                    html += _('Add half of your total Novelty world cost (rounded up).');
                }
                else if( card.name == 'New Economy' )
                {
                    html += _('+1VP per development without a Reassign power (including this one).');
                }
                else if( card.name == 'Galactic Renaissance' )
                {
                    html += _('+1, 2, 3, 4, ... VP for 1, 3, 6, 10, ... VP in chips.');
                }
                else if( card.name == 'Galactic Reserves' )
                {
                    html += _('+1 VP per good (at the end of the game).');
                }
                else if( card.name == 'Galactic Bankers' )
                {
                    html += _('+1 VP per development in your tableau.');
                }
                else if( card.name == 'System Diversification' )
                {
                    html += _('Add half of your total base Reassign-power development cost (rounded up).');
                }
            }

            return html;
        },

        // Update dice in phase for each players
        updatePhaseDice: function( dice, players )
        {
            // Remove previous dices
            var nbPlayers = 0;
            for( var p in players )
            {
                for( var i=1; i<=5; i++ )
                {
                    this.dicePhases[ p ][ i ].removeAll();
                    this.dicePhasesHeader[ p ][ i ].removeAll();
                }

                nbPlayers ++;
            }

            if( typeof players == 'undefined' )
            {
                players = null;
            }

            for( var i in dice )
            {
                var die = dice[i];

                var dieface_type = this.getDicePhaseStockType( die.type, die.type_arg );

                if( die.location == 'cup' )
                {
                    this.cup[ die.location_arg ].addToStockWithId( die.type, die.id );
                }
                else
                {
                    var phase_id = die.location.substr( 5 );    // phaseX
                    if( die.type_arg != 'X' )
                    {

                        var die_origin = 'cup_'+die.location_arg;
                        if( nbPlayers > 1 && die.location_arg == this.player_id )
                        {
                            die_origin = undefined; // In this case, this is current player so dice are already in place
                        }


                        if( players !== null && players[ die.location_arg ].player_choosed_phase == die.id )
                        {
                            this.dicePhasesHeader[ die.location_arg ][ phase_id ].addToStockWithId( dieface_type, die.id, die_origin );
                        }
                        else
                        {
                            this.dicePhases[ die.location_arg ][ phase_id ].addToStockWithId( dieface_type, die.id, die_origin );
                        }
                    }
                }
            }
        },


        getDicePhaseStockType: function( die_type, die_value )
        {
            var dieface = this.gamedatas.dice_types[ die_type ].faces[ die_value-1 ];
            var dieface_type = 7*( die_type-1 ) + ( dieface - 1 );

            return dieface_type;

        },

        updateCredit: function( player_id, credit )
        {
            $('player_credit_'+player_id).innerHTML = credit;

            this.slideToObject( 'credit_'+player_id+'_selector', 'credit_'+player_id+'_'+credit ).play();
        },

        ///////////////////////////////////////////////////
        //// Player's action

        /*

            Here, you are defining methods to handle player's action (ex: results of mouse click on
            game objects).

            Most of the time, these methods:
            _ check the action is possible at this game state.
            _ make a call to the game server

        */

        onCitizenrySelectionChange: function( control_name, item_id )
        {
            var dice = this.citizenry[ this.player_id ].getSelectedItems();
            if( dice.length == 1 )
            {
                if( this.checkAction( 'recruit', false ) )
                {
                    this.ajaxcall( "/rollforthegalaxy/rollforthegalaxy/recruit.html", {
                                                                            lock: true,
                                                                            die: dice[0].id
                                                                         },
                                 this, function( result ) {}, function( is_error) {} );

                    this.citizenry[ this.player_id ].unselectAll();
               }
               else if( this.checkAction( 'removedie' ) )
               {
                    this.confirmationDialog( _('Do you really want to REMOVE this die?'), dojo.hitch( this, function() {

                        this.ajaxcall( "/rollforthegalaxy/rollforthegalaxy/removedie.html", {
                                                                                lock: true,
                                                                                die: dice[0].id
                                                                             },
                                     this, function( result ) {}, function( is_error) {} );
                    } ) );

                    this.citizenry[ this.player_id ].unselectAll();
               }
            }

        },

        onCupSelectionChange: function( control_name, item_id )
        {
            var dice = this.cup[ this.player_id ].getSelectedItems();
            if( dice.length == 1 )
            {
               if( this.checkAction( 'removedie' ) )
               {
                    this.confirmationDialog( _('Do you really want to REMOVE this die?'), dojo.hitch( this, function() {

                        this.ajaxcall( "/rollforthegalaxy/rollforthegalaxy/removedie.html", {
                                                                                lock: true,
                                                                                die: dice[0].id
                                                                             },
                                     this, function( result ) {}, function( is_error) {} );
                    } ) );

                    this.cup[ this.player_id ].unselectAll();
               }
            }

        },

        onDevDiceSelectionChange:  function( control_name, item_id )
        {
            var dice = this.devdice[ this.player_id ].getSelectedItems();
            if( dice.length == 1 )
            {
                if( this.checkAction( 'savedie', false ) )
                {
                    this.ajaxcall( "/rollforthegalaxy/rollforthegalaxy/savedie.html", {
                                                                            lock: true,
                                                                            die: dice[0].id
                                                                         },
                                 this, function( result ) {}, function( is_error) {} );

                }
                else if( this.checkAction( 'recall' ) )
                {
                    this.confirmationDialog( _('Do you really want to RECALL this die to the cup?'), dojo.hitch( this, function() {

                        this.ajaxcall( "/rollforthegalaxy/rollforthegalaxy/recall.html", {
                                                                                lock: true,
                                                                                die: dice[0].id,
                                                                             },
                                     this, function( result ) {}, function( is_error) {} );

                    } ) );
                }
                else if( this.checkAction( 'removedie' ) )
                {
                    this.confirmationDialog( _('Do you really want to REMOVE this die?'), dojo.hitch( this, function() {


                        this.ajaxcall( "/rollforthegalaxy/rollforthegalaxy/removedie.html", {
                                                                                lock: true,
                                                                                die: dice[0].id
                                                                             },
                                     this, function( result ) {}, function( is_error) {} );

                    } ) );

                    this.cup[ this.player_id ].unselectAll();
                }

               this.devdice[ this.player_id ].unselectAll();
            }
        },
        onWorldDiceSelectionChange:  function( control_name, item_id )
        {
            var dice = this.worlddice[ this.player_id ].getSelectedItems();
            if( dice.length == 1 )
            {
                if( this.checkAction( 'savedie', false ) )
                {
                    this.ajaxcall( "/rollforthegalaxy/rollforthegalaxy/savediew.html", {
                                                                            lock: true,
                                                                            die: dice[0].id
                                                                         },
                                 this, function( result ) {}, function( is_error) {} );

               }
                else if( this.checkAction( 'recall' ) )
                {
                    this.confirmationDialog( _('Do you really want to RECALL this die to the cup?'), dojo.hitch( this, function() {

                        this.ajaxcall( "/rollforthegalaxy/rollforthegalaxy/recall.html", {
                                                                                lock: true,
                                                                                die: dice[0].id,
                                                                             },
                                     this, function( result ) {}, function( is_error) {} );

                    } ) );
                }
                else if( this.checkAction( 'removedie' ) )
                {
                    this.confirmationDialog( _('Do you really want to REMOVE this die?'), dojo.hitch( this, function() {


                        this.ajaxcall( "/rollforthegalaxy/rollforthegalaxy/removedie.html", {
                                                                                lock: true,
                                                                                die: dice[0].id
                                                                             },
                                     this, function( result ) {}, function( is_error) {} );

                    } ) );

                    this.cup[ this.player_id ].unselectAll();
                }

               this.worlddice[ this.player_id ].unselectAll();
            }
        },

        onDoNotUse: function()
        {
            this.ajaxcall( "/rollforthegalaxy/rollforthegalaxy/donotuse.html", {
                                                                    lock: true,
                                                                 },
                         this, function( result ) {}, function( is_error) {} );
        },

        onPickScoutedTile: function( control_name, item_id )
        {
            // Choose a tile that have been scouted

            if( typeof item_id != 'undefined' )
            {
                // Is there Improved reconnaissace here?
                if( dojo.query( '#tableau_'+this.player_id+' .card_content_1007' ).length > 0 )
                {
                    // Yes!
                    // => additional choice

                    this.multipleChoiceDialog( _('Improved reconnaissance: do you want to place it on top of the construction stack?'), {
                        1: _('Yes, use Improved reconnaissance'),
                        0: _('No, place it at the bottom')
                    }, dojo.hitch( this, function( choice ) {


                        this.ajaxcall( "/rollforthegalaxy/rollforthegalaxy/pickScoutedTile.html", {
                                                                                lock: true,
                                                                                tile: item_id,
                                                                                side: ( (control_name=='scout_dev') ? 'dev' : 'world' ),
                                                                                top: choice
                                                                             },
                                     this, function( result ) {}, function( is_error) {} );


                        this.scoutedDev.unselectAll();
                        this.scoutedWorld.unselectAll();

                    } ) );

                    return ;
                }


                this.scoutedDev.unselectAll();
                this.scoutedWorld.unselectAll();

                this.ajaxcall( "/rollforthegalaxy/rollforthegalaxy/pickScoutedTile.html", {
                                                                        lock: true,
                                                                        tile: item_id,
                                                                        side: ( (control_name=='scout_dev') ? 'dev' : 'world' ),
                                                                        top: 0
                                                                     },
                             this, function( result ) {}, function( is_error) {} );

            }
        },

        onTableauChangeSelection: function( control_name, item_id )
        {
            if( typeof item_id != 'undefined' )
            {

                if( this.checkAction( 'produce', true ) )
                {
                    this.playerTableau[ this.player_id ].unselectAll();

                    this.ajaxcall( "/rollforthegalaxy/rollforthegalaxy/produce.html", {
                                                                            lock: true,
                                                                            prioritydie:this.getPriorityDie(),
                                                                            tile: item_id
                                                                         },
                                 this, function( result ) {}, function( is_error) {} );

                }
                else if( this.checkAction( 'assign' ) && dojo.hasClass( 'tableau_'+this.player_id+'_item_'+item_id, 'reassign_available' ) )
                {
                    if( this.playerTableau[ this.player_id ].getSelectedItems().length == 1 )
                    {
                        this.showMessage( _("The reassign power of this tile will be used for your next reassign (if possible)"), 'info' );
                    }
                }
                else
                {
                    this.playerTableau[ this.player_id ].unselectAll();
                }
            }
        },

        getPriorityDie: function()
        {
            for( var phase_id=1;phase_id<=5;phase_id++ )
            {
                var selected = this.dicePhases[ this.player_id ][ phase_id ].getSelectedItems();
                if( selected.length == 1 )
                {
                    return selected[0].id;
                }

                selected = this.dicePhasesHeader[ this.player_id ][ phase_id ].getSelectedItems();
                if( selected.length == 1 )
                {
                    return selected[0].id;
                }
            }
            return null;
        },


        onDiceChangeSelection: function( control_name, item_id )
        {
            var parts = control_name.split( '_' );
            var phase_id = parts[3];

            var selection = this.dicePhases[ this.player_id ][ phase_id ].getSelectedItems();
            if( selection.length == 1 )
            {
                // Unselect all other dices
                for( var i=1;i<=5;i++ )
                {
                    if( control_name != 'dicerow_content_'+this.player_id+'_'+i )
                    {
                        this.dicePhases[ this.player_id ][ i ].unselectAll();
                    }
                    this.dicePhasesHeader[ this.player_id ][ i ].unselectAll();
                }


                if( this.checkAction( 'removedie', false ) )
                {
                    this.confirmationDialog( _('Do you really want to REMOVE this die?'), dojo.hitch( this, function() {


                        this.ajaxcall( "/rollforthegalaxy/rollforthegalaxy/removedie.html", {
                                                                                lock: true,
                                                                                die: selection[0].id
                                                                             },
                                     this, function( result ) {}, function( is_error) {} );

                    } ) );
                }
                if( this.checkAction( 'chooseDiceForConstr', false ) )
                {
                    this.ajaxcall( "/rollforthegalaxy/rollforthegalaxy/chooseDiceForConstr.html", {
                                                                            lock: true,
                                                                            die: selection[0].id
                                                                         },
                                 this, function( result ) {}, function( is_error) {} );

                }
            }

        },
        onDiceHeaderChangeSelection: function( control_name, item_id )
        {
            var parts = control_name.split( '_' );
            var phase_id = parts[3];

            var selection = this.dicePhasesHeader[ this.player_id ][ phase_id ].getSelectedItems();
            if( selection.length == 1 )
            {
                // Unselect all other dices
                for( var i=1;i<=5;i++ )
                {
                    if( control_name != 'dicerow_headercontent_'+this.player_id+'_'+i )
                    {
                        this.dicePhasesHeader[ this.player_id ][ i ].unselectAll();
                    }
                    this.dicePhases[ this.player_id ][ i ].unselectAll();
                }

                if( this.checkAction( 'removedie', false ) )
                {
                    this.confirmationDialog( _('Do you really want to REMOVE this die?'), dojo.hitch( this, function() {


                        this.ajaxcall( "/rollforthegalaxy/rollforthegalaxy/removedie.html", {
                                                                                lock: true,
                                                                                die: selection[0].id
                                                                             },
                                     this, function( result ) {}, function( is_error) {} );

                    } ) );

                }
                if( this.checkAction( 'chooseDiceForConstr', false ) )
                {
                    this.ajaxcall( "/rollforthegalaxy/rollforthegalaxy/chooseDiceForConstr.html", {
                                                                            lock: true,
                                                                            die: selection[0].id
                                                                         },
                                 this, function( result ) {}, function( is_error) {} );

                }
            }
        },

        onResourceClick: function( control_name, item_id )
        {
            var parts = control_name.split('_');
            var player_id = parts[2];
            var card_id = parts[4];
            var die_id = item_id;

            if( this.worldResource[ card_id ].getSelectedItems().length == 1 )
            {
                if( this.checkAction( 'ship', false ) )
                {
                    // Show the choice panel
                    this.ajaxcall( "/rollforthegalaxy/rollforthegalaxy/wantToTrade.html", {
                                                                            lock: true,
                                                                            prioritydie: this.getPriorityDie(),
                                                                            die: die_id,
                                                                         },
                                 this, function( result ) {}, function( is_error) {} );

                }
                else if( this.checkAction( 'recall', false ) )
                {
                    this.confirmationDialog( _('Do you really want to RECALL this die to the cup?'), dojo.hitch( this, function() {

                        this.ajaxcall( "/rollforthegalaxy/rollforthegalaxy/recall.html", {
                                                                                lock: true,
                                                                                die: die_id,
                                                                             },
                                     this, function( result ) {}, function( is_error) {} );

                    } ) );
                }
                else if( this.checkAction( 'removedie' ) )
                {
                    this.confirmationDialog( _('Do you really want to REMOVE this die?'), dojo.hitch( this, function() {


                        this.ajaxcall( "/rollforthegalaxy/rollforthegalaxy/removedie.html", {
                                                                                lock: true,
                                                                                die: die_id
                                                                             },
                                     this, function( result ) {}, function( is_error) {} );
                    } ) );
                }


                this.worldResource[ card_id ].unselectAll();
            }
        },

        onShipCancel: function( evt )
        {
            dojo.stopEvent( evt );

            dojo.fadeOut( {node:'shipping_choice_panel', onEnd: function( node ) {  dojo.style( node, 'display', 'none' ) } } ).play();
        },

        onShipConsume: function( evt )
        {
            dojo.stopEvent( evt );

            this.ajaxcall( "/rollforthegalaxy/rollforthegalaxy/consume.html", {
                                                                    lock: true,
                                                                    prioritydie: this.getPriorityDie(),
                                                                    die: this.die_to_ship,
                                                                 },
                         this, function( result ) {}, function( is_error) {} );

            dojo.fadeOut( {node:'shipping_choice_panel', onEnd: function( node ) {  dojo.style( node, 'display', 'none' ) } } ).play();

        },

        onShipTrade: function( evt )
        {
            dojo.stopEvent( evt );


            // Is there Galactic banker here?
            if( dojo.query( '#tableau_'+this.player_id+' .card_content_4' ).length > 0 )
            {
                // Yes!
                // => additional choice

                this.multipleChoiceDialog( _('Galactic Bankers: do you want to spend $1 for +1 VP?'), {
                    1: _('Yes, use Galactic Bankers.'),
                    0: _('No, standard trade.')
                }, dojo.hitch( this, function( choice ) {


                    this.ajaxcall( "/rollforthegalaxy/rollforthegalaxy/trade.html", {
                                                                            lock: true,
                                                                            die: this.die_to_ship,
                                                                            prioritydie: this.getPriorityDie(),
                                                                            gb: choice
                                                                         },
                                 this, function( result ) {}, function( is_error) {} );

                    dojo.fadeOut( {node:'shipping_choice_panel', onEnd: function( node ) {  dojo.style( node, 'display', 'none' ) } } ).play();


                } ) );

                return ;
            }


            // Standard behaviour
            this.ajaxcall( "/rollforthegalaxy/rollforthegalaxy/trade.html", {
                                                                    lock: true,
                                                                    die: this.die_to_ship,
                                                                    prioritydie: this.getPriorityDie(),
                                                                    gb: 0
                                                                 },
                         this, function( result ) {}, function( is_error) {} );

            dojo.fadeOut( {node:'shipping_choice_panel', onEnd: function( node ) {  dojo.style( node, 'display', 'none' ) } } ).play();
        },

        onPlaceDiceOnRow: function( evt )
        {
            // dicerow_background_<pid>_<phase_id>
            var parts = evt.currentTarget.id.split( '_' );
            var phase_id = parts[3];

            // Get selected die
            for( var i=1;i<=5;i++ )
            {
                var die = null;
                var selected = this.dicePhases[ this.player_id ][ i ].getSelectedItems();
                if( selected.length == 1 )
                {
                    // We found our die!
                    die = selected[0];
                }
                else
                {
                    var selected = this.dicePhasesHeader[ this.player_id ][ i ].getSelectedItems();
                    if( selected.length == 1 )
                    {
                        // We found our die!
                        die = selected[0];
                    }
                }

                if( die !== null )
                {
                    var use_reassign_power = null;

                    var power_selected = this.playerTableau[ this.player_id ].getSelectedItems();
                    if( power_selected.length == 1 )
                    {
                        use_reassign_power = power_selected[0].id;
                    }

                    // Moving it to this phase
                    this.ajaxcall( "/rollforthegalaxy/rollforthegalaxy/reassign.html", {
                                                                            lock: true,
                                                                            die: die.id,
                                                                            power: use_reassign_power,
                                                                            phase: phase_id,
                                                                            activate: false
                                                                         },
                                 this, function( result ) {}, function( is_error) {} );

                    return;
                }

            }

            this.showMessage( _("You must first select the worker (die) you want to reassign to this phase."), 'info' );
        },

        onPlaceDiceOnHeader: function( evt )
        {
            // dicerow_header_<pid>_<phase_id>
            var parts = evt.currentTarget.id.split( '_' );
            var phase_id = parts[3];

            // Get selected die
            for( var i=1;i<=5;i++ )
            {
                var die = null;
                var selected = this.dicePhases[ this.player_id ][ i ].getSelectedItems();
                if( selected.length == 1 )
                {
                    // We found our die!
                    die = selected[0];
                }
                else
                {
                    var selected = this.dicePhasesHeader[ this.player_id ][ i ].getSelectedItems();
                    if( selected.length == 1 )
                    {
                        // We found our die!
                        die = selected[0];
                    }
                }

                if( die !== null )
                {
                    // Moving it to this phase
                    this.ajaxcall( "/rollforthegalaxy/rollforthegalaxy/reassign.html", {
                                                                            lock: true,
                                                                            die: die.id,
                                                                            phase: phase_id,
                                                                            activate: true
                                                                         },
                                 this, function( result ) {}, function( is_error) {} );

                    return;
                }
            }

            this.showMessage( _("You must first select the worker (die) you want to reassign to use to activate this phase."), 'info' );
        },

        onChooseComboFlip: function()
        {
            this.ajaxcall( "/rollforthegalaxy/rollforthegalaxy/chooseComboFlip.html", {
                                                                    lock: true
                                                                 },
                         this, function( result ) {}, function( is_error) {} );
        },

        onDoneChooseCombo: function(  )
        {
            this.ajaxcall( "/rollforthegalaxy/rollforthegalaxy/doneChooseCombo.html", {
                                                                    lock: true
                                                                 },
                         this, function( result ) {}, function( is_error) {} );

        },

        onDictate: function()
        {
            // Get selected die
            var die = null;
            for( var i=1;i<=5;i++ )
            {
                var selected = this.dicePhases[ this.player_id ][ i ].getSelectedItems();
                if( selected.length == 1 )
                {
                    // We found our die!
                    die = selected[0];
                }
                else
                {
                    var selected = this.dicePhasesHeader[ this.player_id ][ i ].getSelectedItems();
                    if( selected.length == 1 )
                    {
                        // We found our die!
                        die = selected[0];
                    }
                }
            }

            if( die === null )
            {
                this.showMessage( _("Please first select a die to return to your dice cup to gain a Dictate reassign power."), 'info' );
            }
            else
            {
                this.ajaxcall( "/rollforthegalaxy/rollforthegalaxy/dictate.html", {
                                                                        lock: true,
                                                                        die:die.id
                                                                     },
                             this, function( result ) {}, function( is_error) {} );
            }
        },

        onResetAssign: function()
        {
            this.ajaxcall( "/rollforthegalaxy/rollforthegalaxy/resetAssign.html", {
                                                                    lock: true
                                                                 },
                         this, function( result ) {}, function( is_error) {} );
        },

        onDoneAssign: function(  )
        {
            this.ajaxcall( "/rollforthegalaxy/rollforthegalaxy/doneAssign.html", {
                                                                    lock: true
                                                                 },
                         this, function( result ) {}, function( is_error) {} );

        },

        onScout: function()
        {
            this.ajaxcall( "/rollforthegalaxy/rollforthegalaxy/scout.html", {
                                                                    lock: true,
                                                                    prioritydie:this.getPriorityDie()
                                                                 },
                         this, function( result ) {}, function( is_error) {} );
        },
        onScoutDiscard: function()
        {
            var selection1 = this.playerWorldInBuilt[ this.player_id ].getSelectedItems();
            var selection2 = this.playerDevInBuilt[ this.player_id ].getSelectedItems();

            if( ( selection1.length + selection2.length ) == 0 )
            {
                this.showMessage( _("You must first select tile(s) to discard in your construction zone. Each discarded tile allows you to pick one more tile during Scout action."), 'info' );
            }
            else
            {
                var discard_list = '';
                for( var i in selection1 )
                {
                    discard_list += selection1[ i ].id+';';
                }
                for( var i in selection2 )
                {
                    discard_list += selection2[ i ].id+';';
                }

                this.ajaxcall( "/rollforthegalaxy/rollforthegalaxy/scoutdiscard.html", {
                                                                        lock: true,
                                                                        cards: discard_list
                                                                     },
                             this, function( result ) {}, function( is_error) {} );

            }
        },

        onStock: function()
        {
            this.ajaxcall( "/rollforthegalaxy/rollforthegalaxy/stock.html", {
                                                                    lock: true,
                                                                    prioritydie: this.getPriorityDie()
                                                                 },
                         this, function( result ) {}, function( is_error) {} );
        },


        onManageDone: function()
        {
            this.ajaxcall( "/rollforthegalaxy/rollforthegalaxy/manageDone.html", {
                                                                    lock: true
                                                                 },
                         this, function( result ) {}, function( is_error) {} );
        },


        /* Example:

        onMyMethodToCall1: function( evt )
        {
            console.log( 'onMyMethodToCall1' );

            // Preventing default browser reaction
            dojo.stopEvent( evt );

            // Check that this action is possible (see "possibleactions" in states.inc.php)
            if( ! this.checkAction( 'myAction' ) )
            {   return; }

            this.ajaxcall( "/rollforthegalaxy/rollforthegalaxy/myAction.html", {
                                                                    lock: true,
                                                                    myArgument1: arg1,
                                                                    myArgument2: arg2,
                                                                    ...
                                                                 },
                         this, function( result ) {

                            // What to do after the server call if it succeeded
                            // (most of the time: nothing)

                         }, function( is_error) {

                            // What to do after the server call in anyway (success or failure)
                            // (most of the time: nothing)

                         } );
        },

        */


        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        /*
            setupNotifications:

            In this method, you associate each of your game notifications with your local method to handle it.

            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                  your rollforthegalaxy.game.php file.

        */
        setupNotifications: function()
        {
            console.log( 'notifications subscriptions setup' );

            // Example 1: standard notification handling
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );

            // Example 2: standard notification handling + tell the user interface to wait
            //            during 3 seconds after calling the method in order to let the players
            //            see what is happening in the game.
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            // this.notifqueue.setSynchronous( 'cardPlayed', 3000 );
            //



            dojo.subscribe( 'chooseComboFlip', this, 'notif_chooseComboFlip' );
            dojo.subscribe( 'dicerolled_nowait', this, 'notif_dicerolled' );
            dojo.subscribe( 'dicerolled', this, 'notif_dicerolled' );
            this.notifqueue.setSynchronous( 'dicerolled', 3000 );
            dojo.subscribe( 'movePhaseDie', this, 'notif_movePhaseDie' );
            dojo.subscribe( 'consumeDie', this, 'notif_consumeDie' );
            dojo.subscribe( 'updateCredit', this, 'notif_updateCredit' );
            dojo.subscribe( 'dice_to_construction', this, 'notif_dice_to_construction' );
            this.notifqueue.setSynchronous( 'dice_to_construction', 200 );

            dojo.subscribe( 'card_built', this, 'notif_card_built' );
            this.notifqueue.setSynchronous( 'pre_card_built', 2000 );
            this.notifqueue.setSynchronous( 'card_built', 1200 );

            dojo.subscribe( 'recruitDie', this, 'notif_recruitDie' );
            dojo.subscribe( 'returnedDie', this, 'notif_returnedDie' );
            dojo.subscribe( 'dieShipped', this, 'notif_dieShipped' );

            dojo.subscribe( 'produce', this, 'notif_produce' );
            this.notifqueue.setSynchronous( 'produce', 500 );

            dojo.subscribe( 'tradeinfos', this, 'notif_tradeinfos' );
            dojo.subscribe( 'scorevp', this, 'notif_scorevp' );
            dojo.subscribe( 'scouted', this, 'notif_scouted' );
            dojo.subscribe( 'pickScouted', this, 'notif_pickScouted' );
            dojo.subscribe( 'newConstruction', this, 'notif_newConstruction' );

            dojo.subscribe( 'newdie', this, 'notif_newdie' );
            dojo.subscribe( 'debug_ac', this, 'notif_debug_ac' );
            dojo.subscribe( 'debug_act', this, 'notif_debug_act' );

            dojo.subscribe( 'removedie', this, 'notif_removedie' );
            dojo.subscribe( 'recall', this, 'notif_recall' );
            dojo.subscribe( 'tmpdie', this, 'notif_tmpdie' );
            dojo.subscribe( 'savedie', this, 'notif_savedie' );

            dojo.subscribe( 'resetConstruction', this, 'notif_resetConstruction' );

            dojo.subscribe( 'scoutdiscard', this, 'notif_scoutdiscard' );

            dojo.subscribe( 'phasesToActive', this, 'notif_phasesToActive' );

            dojo.subscribe( 'initialScores', this, 'notif_initialScores' );
            dojo.subscribe( 'score', this, 'notif_score' );

            this.notifqueue.setSynchronous( 'pauseBeforeRecruit', 1000 );
        },

        updatePhases: function( phases )
        {
            var bAtLeastOnePhase = false;
            dojo.query( '.dicerow_header' ).addClass( 'phase_not_selected' );

            for( var i in phases )
            {
                bAtLeastOnePhase = true;
                dojo.query( '.dicerow_header_'+i ).removeClass( 'phase_not_selected' );
            }

            if( ! bAtLeastOnePhase )
            {
                // No phases activated => "assign" case => do not display any
                dojo.query( '.dicerow_header' ).removeClass( 'phase_not_selected' );
            }

        },

        notif_initialScores: function( notif )
        {
            for( var i in notif.args.scores )
            {
                this.scoreCtrl[ i ].toValue( notif.args.scores[i] );
            }
        },

        notif_score: function( notif )
        {
            this.scoreCtrl[ notif.args.player_id ].incValue( notif.args.score );
        },

        notif_phasesToActive: function( notif )
        {
            this.updatePhases( notif.args.phases );
        },

        notif_chooseComboFlip: function( notif )
        {
            this.playerWorldInBuilt[ notif.args.player_id ].removeAll();
            this.playerWorldInBuilt[ notif.args.player_id ].addToStockWithId( notif.args.bw.type, notif.args.bw.id );

            this.playerDevInBuilt[ notif.args.player_id ].removeAll();
            this.playerDevInBuilt[ notif.args.player_id ].addToStockWithId( notif.args.bd.type, notif.args.bd.id );
        },

        notif_scoutdiscard: function( notif )
        {
            // Remove development cards and update counter
            for( var i in notif.args.dev_cards )
            {
                this.playerDevInBuilt[ notif.args.player_id ].removeFromStockById( notif.args.dev_cards[i] );
            }
            if( notif.args.dev_cards.length > 0 )
            {
                $('dev_in_built_counter_'+notif.args.player_id).innerHTML = toint( $('dev_in_built_counter_'+notif.args.player_id).innerHTML ) - notif.args.dev_cards.length;
            }

            // Remove world cards and update counter
            for( var i in notif.args.world_cards )
            {
                this.playerWorldInBuilt[ notif.args.player_id ].removeFromStockById( notif.args.world_cards[i] );
            }
            if( notif.args.world_cards.length > 0 )
            {
                $('world_in_built_counter_'+notif.args.player_id).innerHTML = toint( $('world_in_built_counter_'+notif.args.player_id).innerHTML ) - notif.args.world_cards.length;
            }
        },

        notif_dicerolled: function( notif )
        {
            // Empty cups
            for( var player_id in this.gamedatas.players )
            {
                this.cup[ player_id ].removeAll();
            }

            if( $('dictate') )
            {
                dojo.style( 'dictate', 'display', 'inline-block' );
            }

            this.updatePhaseDice( notif.args.dice, notif.args.players );

            if( typeof notif.args.available != 'undefined' )
            {
                this.updateAvailableAssignPowers( notif.args.available );
                this.playerTableau[ this.player_id ].unselectAll();
            }
        },
        notif_tmpdie: function( notif )
        {
            for( var i in notif.args.dice )
            {
                var die = notif.args.dice[i];
                var phase_id = die.location.substr( 5 );    // phaseX
                var dieface_type = this.getDicePhaseStockType( die.type, die.type_arg );

                this.dicePhases[ die.location_arg ][ phase_id ].addToStockWithId( dieface_type, die.id );
            }

        },

        notif_recruitDie: function( notif )
        {

            this.cup[ notif.args.player_id ].addToStockWithId( notif.args.die.type, notif.args.die.id, 'citizenry_'+notif.args.player_id+'_item_'+notif.args.die.id );
            this.citizenry[ notif.args.player_id ].removeFromStockById( notif.args.die.id );
        },

        notif_movePhaseDie: function( notif )
        {
            console.log( 'notif_movePhaseDie' );
            console.log( notif );

            var origin_phase_id = notif.args.die.location.substr( 5 );

            var dieface_type = this.getDicePhaseStockType( notif.args.die.type, notif.args.die.type_arg );

            var die_origin = 'dicerow_content_'+this.player_id+'_'+origin_phase_id+'_item_'+notif.args.die.id;

            if( notif.args.wasselection )
            {
                die_origin = 'dicerow_headercontent_'+this.player_id+'_'+origin_phase_id+'_item_'+notif.args.die.id;
            }

            if( ! $(die_origin) )
            {
                die_origin = undefined;
            }

            if( notif.args.activate )
            {
                this.dicePhasesHeader[ this.player_id ][ notif.args.phase ].addToStockWithId( dieface_type, notif.args.die.id, die_origin );
            }
            else
            {
                this.dicePhases[ this.player_id ][ notif.args.phase ].addToStockWithId( dieface_type, notif.args.die.id, die_origin );
            }

            if( notif.args.wasselection )
            {
                this.dicePhasesHeader[ this.player_id ][ origin_phase_id ].removeFromStockById( notif.args.die.id );
            }
            else
            {
                this.dicePhases[ this.player_id ][ origin_phase_id ].removeFromStockById( notif.args.die.id );
            }

            if( notif.args.power !== null )
            {
                if( notif.args.power == 'Dictate' )
                {
                    this.showMessage( _("You use your dictate power to move this die"), 'info' );
                }
            }

            if( typeof notif.args.powertile != 'undefined' && notif.args.powertile != null )
            {
                dojo.removeClass( 'tableau_'+this.player_id+'_item_'+notif.args.powertile, 'reassign_available' );
                this.playerTableau[ this.player_id ].unselectAll();
            }
        },

        notif_consumeDie: function( notif )
        {
            // Die goes from phase => citizenry

            var origin_phase_id = notif.args.die.location.substr( 5 );

            var die_origin = 'dicerow_content_'+notif.args.player_id+'_'+origin_phase_id+'_item_'+notif.args.die.id;
            var bWasSelection = false;
            if( ! $(die_origin ) )
            {
                die_origin = 'dicerow_headercontent_'+notif.args.player_id+'_'+origin_phase_id+'_item_'+notif.args.die.id;
                bWasSelection= true;
            }

            this.citizenry[ notif.args.player_id ].addToStockWithId( notif.args.die.type, notif.args.die.id, die_origin );

            if( bWasSelection )
            {
                this.dicePhasesHeader[ notif.args.player_id ][ origin_phase_id ].removeFromStockById( notif.args.die.id );
            }
            else
            {
                this.dicePhases[ notif.args.player_id ][ origin_phase_id ].removeFromStockById( notif.args.die.id );
            }
        },

        notif_produce: function( notif )
        {
            if( typeof notif.args.from_stock != 'undefined' )
            {
                this.worldResource[ notif.args.card_id ].addToStockWithId( notif.args.die.type, notif.args.die.id, die_origin );
            }
            else
            {
                var origin_phase_id = notif.args.die.location.substr( 5 );

                var die_origin = 'dicerow_content_'+notif.args.player_id+'_'+origin_phase_id+'_item_'+notif.args.die.id;
                var bWasSelection = false;
                if( ! $(die_origin ) )
                {
                    die_origin = 'dicerow_headercontent_'+notif.args.player_id+'_'+origin_phase_id+'_item_'+notif.args.die.id;
                    bWasSelection= true;
                }

                this.worldResource[ notif.args.card_id ].addToStockWithId( notif.args.die.type, notif.args.die.id, die_origin );

                if( bWasSelection )
                {
                    this.dicePhasesHeader[ notif.args.player_id ][ origin_phase_id ].removeFromStockById( notif.args.die.id );
                }
                else
                {
                    this.dicePhases[ notif.args.player_id ][ origin_phase_id ].removeFromStockById( notif.args.die.id );
                }
            }
        },

        notif_dieShipped: function( notif )
        {
            // Die goes from world to citizenry

            var die_origin = 'tableau_'+notif.args.player_id+'_item_'+notif.args.world_id;

            this.citizenry[ notif.args.player_id ].addToStockWithId( notif.args.die.type, notif.args.die.id, die_origin );

            this.worldResource[ notif.args.world_id ].removeFromStockById( notif.args.die.id );

        },

        notif_returnedDie: function( notif )
        {
            // Die goes from phase => cup

            var origin_phase_id = notif.args.die.location.substr( 5 );
            var die_origin = 'dicerow_content_'+notif.args.player_id+'_'+origin_phase_id+'_item_'+notif.args.die.id;

            var bWasSelection = false;
            if( ! $(die_origin ) )
            {
                die_origin = 'dicerow_headercontent_'+notif.args.player_id+'_'+origin_phase_id+'_item_'+notif.args.die.id;
                bWasSelection= true;
            }

            if( ! $(die_origin) )
            {
                die_origin = undefined;
            }

            this.cup[ notif.args.player_id ].addToStockWithId( notif.args.die.type, notif.args.die.id, die_origin );

            if( bWasSelection )
            {
                this.dicePhasesHeader[ notif.args.player_id ][ origin_phase_id ].removeFromStockById( notif.args.die.id );
            }
            else
            {
                this.dicePhases[ notif.args.player_id ][ origin_phase_id ].removeFromStockById( notif.args.die.id );
            }

            if( typeof notif.args.dictate != 'undefined' && notif.args.player_id == this.player_id )
            {
                // Is there backup planning there
                if( dojo.query( '#tableau_'+this.player_id+' .card_content_141' ).length > 0 )
                {
                    // Yes ! So never hide Dictate button.
                }
                else
                {
                    dojo.style( 'dictate', 'display', 'none' );
                }

                if( notif.args.triple == false )
                {
                    this.showMessage( _("Dictate: you can now reassign 1 die to any phase."), 'info' );
                }
                else
                {
                    this.showMessage( _("Dictate: you can now reassign 3 dice to any phase."), 'info' );
                }
            }

        },

        notif_recall: function( notif )
        {
            this.cup[ notif.args.player_id ].addToStockWithId( notif.args.die.type, notif.args.die.id, this.getDieDivIdAnywhere( notif.args.die, notif.args.player_id ) );

            this.removeDieFromAnywhere( notif.args.die );
        },

        notif_removedie: function( notif )
        {
            // Die removed from the game
            this.removeDieFromAnywhere( notif.args.die );
        },

        getDieDivIdAnywhere: function( die, player_id )
        {
            if( die.location == 'cup' )
            {
                return 'cup_'+die.location_arg+'_item_'+die.id;
            }
            else if( die.location == 'citizenry' )
            {
                return 'citizenry_'+die.location_arg+'_item_'+die.id;
            }
            else if( die.location.substr( 0,5 ) == 'phase' )
            {
                var phase_id = die.location.substr(5);
                if( $( 'dicerow_content_'+player_id+'_'+phase_id+'_item_'+die.id ) )
                {   return 'dicerow_content_'+player_id+'_'+phase_id+'_item_'+die.id;   }
                else
                {   return 'dicerow_headercontent_'+player_id+'_'+phase_id+'_item_'+die.id;   }
            }
            else if( die.location == 'worldconstruct' )
            {
                return 'world_dice_'+die.location_arg+'_item_'+die.id;
            }
            else if( die.location == 'devconstruct' )
            {
                return 'dev_dice_'+die.location_arg+'_item_'+die.id;
            }
            else if( die.location == 'resource' )
            {
                return 'resourcezone_tableau_'+player_id+'_item_'+die.location_arg+'_item_'+die.id;
            }
        },

        removeDieFromAnywhere: function( die )
        {
            if( die.location == 'cup' )
            {
                this.cup[ die.location_arg ].removeFromStockById( die.id );
            }
            else if( die.location == 'citizenry' )
            {
                this.citizenry[ die.location_arg ].removeFromStockById( die.id );
            }
            else if( die.location.substr( 0,5 ) == 'phase' )
            {
                this.dicePhases[ die.location_arg ][ die.location.substr( 5 ) ].removeFromStockById( die.id );
                this.dicePhasesHeader[ die.location_arg ][ die.location.substr( 5 ) ].removeFromStockById( die.id );
            }
            else if( die.location == 'worldconstruct' )
            {
                this.worlddice[ die.location_arg ].removeFromStockById( die.id );
            }
            else if( die.location == 'devconstruct' )
            {
                this.devdice[ die.location_arg ].removeFromStockById( die.id );
            }
            else if( die.location == 'resource' )
            {
                this.worldResource[ die.location_arg ].removeFromStockById( die.id );
            }
        },

        notif_dice_to_construction: function( notif )
        {
            // Die goes from phase => construction

            var origin_phase_id = notif.args.die.location.substr( 5 );

            var die_origin = 'dicerow_content_'+notif.args.player_id+'_'+origin_phase_id+'_item_'+notif.args.die.id;
            var bWasSelection = false;
            if( ! $(die_origin ) )
            {
                die_origin = 'dicerow_headercontent_'+notif.args.player_id+'_'+origin_phase_id+'_item_'+notif.args.die.id;
                bWasSelection= true;
            }

            if( notif.args.zone == 'dev' )
            {
                this.devdice[ notif.args.player_id ].addToStockWithId( notif.args.die.type, notif.args.die.id, die_origin );
            }
            else
            {
                this.worlddice[ notif.args.player_id ].addToStockWithId( notif.args.die.type, notif.args.die.id, die_origin );
            }

            if( bWasSelection )
            {
                this.dicePhasesHeader[ notif.args.player_id ][ origin_phase_id ].removeFromStockById( notif.args.die.id );
            }
            else
            {
                this.dicePhases[ notif.args.player_id ][ origin_phase_id ].removeFromStockById( notif.args.die.id );
            }

        },

        notif_savedie: function( notif )
        {
            // From dev/world zone => cup

            var die = notif.args.die;
            if( notif.args.zone == 'dev' )
            {
                var die_origin = 'dev_dice_'+notif.args.player_id+'_item_'+die.id;
                this.cup[ notif.args.player_id ].addToStockWithId( die.type, die.id, die_origin );
                this.devdice[ notif.args.player_id ].removeFromStockById( die.id );
            }
            else
            {
                var die_origin = 'world_dice_'+notif.args.player_id+'_item_'+die.id;
                this.cup[ notif.args.player_id ].addToStockWithId( die.type, die.id, die_origin );
                this.worlddice[ notif.args.player_id ].removeFromStockById( die.id );
            }
        },

        notif_card_built: function( notif )
        {
            var card_origin = 'dev_in_built_'+notif.args.player_id;
            if( notif.args.zone == 'world' )
            {
                card_origin = 'world_in_built_'+notif.args.player_id;
            }

            this.playerTableau[ notif.args.player_id ].addToStockWithId( notif.args.card.type, notif.args.card.id, card_origin );

            $('tableau_nbr_'+notif.args.player_id).innerHTML = toint( $('tableau_nbr_'+notif.args.player_id).innerHTML )+1;

            if( toint( $('tableau_nbr_'+notif.args.player_id).innerHTML ) == 12 )
            {
                this.showMessage( _("A tableau just reached 12 tiles: this is the last turn!"), 'info' );
            }

            if( notif.args.card.type == 32 && this.player_id == notif.args.player_id )
            {
                dojo.addClass( 'tableau_panel_'+this.player_id, 'tableau_with_al' );
            }

            if( notif.args.zone == 'world' )
            {
                this.playerWorldInBuilt[ notif.args.player_id ].removeFromStockById( notif.args.card.id );
                $('world_in_built_counter_'+notif.args.player_id).innerHTML = toint( $('world_in_built_counter_'+notif.args.player_id).innerHTML ) -1;
                if( notif.args.tile_back != null )
                {
                    this.playerWorldInBuilt[ notif.args.player_id ].addToStockWithId( notif.args.tile_back.type, notif.args.tile_back.id );
                }
            }
            else
            {
                this.playerDevInBuilt[ notif.args.player_id ].removeFromStockById( notif.args.card.id );
                $('dev_in_built_counter_'+notif.args.player_id).innerHTML = toint( $('dev_in_built_counter_'+notif.args.player_id).innerHTML ) -1;

                if( notif.args.tile_back != null )
                {
                    this.playerDevInBuilt[ notif.args.player_id ].addToStockWithId( notif.args.tile_back.type, notif.args.tile_back.id );
                }
            }

            // Remove associated dice
            for( var i in notif.args.dice )
            {
                var die = notif.args.dice[i];

                if( notif.args.zone == 'dev' )
                {
                    var die_origin = 'dev_dice_'+notif.args.player_id+'_item_'+die.id;
                }
                else
                {
                    var die_origin = 'world_dice_'+notif.args.player_id+'_item_'+die.id;
                }

                this.citizenry[ notif.args.player_id ].addToStockWithId( die.type, die.id, die_origin );

                if( notif.args.zone == 'dev' )
                {
                    this.devdice[ notif.args.player_id ].removeFromStockById( die.id );
                }
                else
                {
                    this.worlddice[ notif.args.player_id ].removeFromStockById( die.id );
                }

            }

            // Update tableau count
            $('tableau_nbr_'+notif.args.player_id).innerHTML = notif.args.tableaucount;

            // Score
            $('player_vp_'+notif.args.player_id).innerHTML = notif.args.score.player_vp_chip;
            this.scoreCtrl[ notif.args.player_id ].toValue( notif.args.score.player_score );


        },

        notif_updateCredit: function( notif )
        {
            this.updateCredit( notif.args.player_id, notif.args.credit );
        },

        notif_tradeinfos: function( notif )
        {
            dojo.style( 'shipping_choice_panel', 'display', 'block' );
            dojo.style( 'shipping_choice_panel', 'opacity', 0 );
            dojo.fadeIn( {node:'shipping_choice_panel'} ).play();

            this.placeOnObject( 'shipping_choice_panel', 'resourcezone_tableau_'+this.player_id+'_item_'+notif.args.world+'_item_'+notif.args.die.id );

            if( notif.args.trade.vp == 0 )
            {
                $('shipping_choice_trade').innerHTML = dojo.string.substitute( _('Trade for $${value}'), { value: notif.args.trade.c } );
            }
            else
            {
                $('shipping_choice_trade').innerHTML = dojo.string.substitute( _('Trade for $${value} + ${vp} VP'), { value: notif.args.trade.c, vp: notif.args.trade.vp } );
            }

            if( notif.args.consume.c == 0 )
            {
                $('shipping_choice_consume').innerHTML = dojo.string.substitute( _('Consume for ${value} VP'), { value: notif.args.consume.vp } );
            }
            else
            {
                $('shipping_choice_consume').innerHTML = dojo.string.substitute( _('Consume for ${value} VP + $${credit}'), { value: notif.args.consume.vp, credit: notif.args.consume.c } );
            }

            this.die_to_ship = notif.args.die.id;
        },

        notif_scorevp: function( notif )
        {
            $('player_vp_'+notif.args.player_id).innerHTML = notif.args.score.player_vp_chip;
            this.scoreCtrl[ notif.args.player_id ].toValue( notif.args.score.player_score );
            $('vp_stock').innerHTML = ( toint( $('vp_stock').innerHTML ) - toint( notif.args.gain ) );

            if( toint( $('vp_stock').innerHTML ) <= 0 )
            {
                this.showMessage( _("There is no more VP chips in stock: the game ends at the end of this turn!"), 'info' );
            }
        },

        notif_scouted: function( notif )
        {
            for( var i in notif.args.tiles.dev )
            {
                var tile = notif.args.tiles.dev[i];

                this.scoutedDev.addToStockWithId( tile.type, tile.id );
                this.showScoutPanel();
            }
            for( var i in notif.args.tiles.world )
            {
                var tile = notif.args.tiles.world[i];

                this.scoutedWorld.addToStockWithId( tile.type, tile.id );
                this.showScoutPanel();
            }

        },


        showScoutPanel: function()
        {
            dojo.style( 'scout_panel', 'display', 'block' );
            $('pagemaintitletext').innerHTML = _("You must choose on which sides (and which order) these tiles will be placed under your construction areas");
            dojo.style('generalactions', 'display', 'none' );
        },
        hideScoutPanel: function()
        {
            dojo.style( 'scout_panel', 'display', 'none' );
            $('pagemaintitletext').innerHTML = _("Explore: You must scout or stock");
            dojo.style('generalactions', 'display', 'inline-block' );
        },

        notif_pickScouted: function( notif )
        {
            var pos = ':first';
            if( notif.args.top )
            {   pos = undefined; }

            if( notif.args.target == 'dev' )
            {

                this.playerDevInBuilt[ this.player_id ].addToStockWithId( notif.args.tile.type, notif.args.tile.id, 'scout_dev_item_'+notif.args.tile.id, pos );
                this.scoutedDev.removeFromStockById( notif.args.tile.id );
                this.scoutedWorld.removeFromStockById( notif.args.tile.id );
                $('dev_in_built_counter_'+this.player_id).innerHTML = toint( $('dev_in_built_counter_'+this.player_id).innerHTML ) +1;
            }
            else if( notif.args.target == 'world' )
            {
                this.playerWorldInBuilt[ this.player_id ].addToStockWithId( notif.args.tile.type, notif.args.tile.id, 'scout_world_item_'+notif.args.tile.id, pos );
                this.scoutedDev.removeFromStockById( notif.args.tile.id );
                this.scoutedWorld.removeFromStockById( notif.args.tile.id );
                $('world_in_built_counter_'+this.player_id).innerHTML = toint( $('world_in_built_counter_'+this.player_id).innerHTML ) +1;
            }

            if( this.scoutedDev.count() == 0 )
            {
                this.hideScoutPanel();
            }
        },

        notif_newConstruction: function( notif )
        {
            if( notif.args.player_id == this.player_id )
            {
                // Already processed during notif_pickScouted
                return ;
            }

            if( notif.args.target == 'dev' )
            {
                if( notif.args.visible )
                {
                    this.playerDevInBuilt[ notif.args.player_id ].addToStockWithId( notif.args.tile.type, notif.args.tile.id );
                }
                $('dev_in_built_counter_'+notif.args.player_id).innerHTML = toint( $('dev_in_built_counter_'+notif.args.player_id).innerHTML ) +1;
            }
            else if( notif.args.target == 'world' )
            {
                if( notif.args.visible )
                {
                    this.playerWorldInBuilt[ notif.args.player_id ].addToStockWithId( notif.args.tile.type, notif.args.tile.id );
                }
                $('world_in_built_counter_'+notif.args.player_id).innerHTML = toint( $('world_in_built_counter_'+notif.args.player_id).innerHTML ) +1;
            }
        },

        notif_resetConstruction: function( notif )
        {
            if( notif.args.zone == 'dev' )
            {
                this.playerDevInBuilt[ this.player_id ].removeAll();

                for( var i in notif.args.tiles )
                {
                    var tile = notif.args.tiles[i];
                    this.playerDevInBuilt[ this.player_id ].addToStockWithId( tile.type, tile.id );
                }

            }
            else if( notif.args.zone == 'world' )
            {
                this.playerWorldInBuilt[ this.player_id ].removeAll();

                for( var i in notif.args.tiles )
                {
                    var tile = notif.args.tiles[i];
                    this.playerWorldInBuilt[ this.player_id ].addToStockWithId( tile.type, tile.id );
                }

            }
        },

        notif_newdie: function( notif )
        {
            if( notif.args.target == 'cup' )
            {
                this.cup[ notif.args.player_id ].addToStockWithId( notif.args.die.type, notif.args.die.id, 'tableau_'+notif.args.player_id+'_item_'+notif.args.tile_id );
            }
            else if( notif.args.target == 'citizenry' )
            {
                this.citizenry[ notif.args.player_id ].addToStockWithId( notif.args.die.type, notif.args.die.id, 'tableau_'+notif.args.player_id+'_item_'+notif.args.tile_id );
            }
        },

        notif_debug_ac: function( notif )
        {
            console.log('debug_ac: ' + notif.args.tile)
            if( notif.args.side == 'dev' )
            {
                this.playerDevInBuilt[ this.player_id ].addToStockWithId( notif.args.tile.type, notif.args.tile.id);
                $('dev_in_built_counter_'+this.player_id).innerHTML = toint( $('dev_in_built_counter_'+this.player_id).innerHTML ) +1;
            }
            else if( notif.args.side == 'world' )
            {
                this.playerWorldInBuilt[ this.player_id ].addToStockWithId( notif.args.tile.type, notif.args.tile.id);
                $('world_in_built_counter_'+this.player_id).innerHTML = toint( $('world_in_built_counter_'+this.player_id).innerHTML ) +1;
            }
        },

        notif_debug_act: function( notif )
        {
            console.log('debug_act: ' + notif.args.tile)
            this.playerTableau[ notif.args.tile.location_arg ].addToStockWithId( notif.args.tile.type, notif.args.tile.id );
        }

   });
});
