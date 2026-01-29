<?php
 /**
  *------
  * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
  * RollForTheGalaxy implementation : © <Your name here> <Your email address here>
  *
  * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
  * See http://en.boardgamearena.com/#!doc/Studio for more information.
  * -----
  *
  * rollforthegalaxy.game.php
  *
  * This is the main file for your game logic.
  *
  * In this PHP file, you are going to defines the rules of the game.
  *
  */

use Bga\GameFramework\UserException;
use Bga\GameFramework\SystemException;

class RollForTheGalaxy extends Bga\GameFramework\Table
{
	function __construct( )
	{
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();

        self::initGameStateLabels( array(

                "vp_stock" => 10,
                'current_effect_phase' => 11,
                'current_effect_card' => 12,
                'current_effect_beforebuild' => 13,
                'saved_dice_nbr' => 14,
                'selectedphases' => 15,

            //    "my_first_global_variable" => 10,
            //    "my_second_global_variable" => 11,
            //      ...
            //    "my_first_game_variant" => 100,
            //    "my_second_game_variant" => 101,
            //      ...
        ) );


        $this->tiles = self::getNew( "module.common.deck" );
        $this->tiles->init( "tile" );

        $this->dice = self::getNew( "module.common.deck" );
        $this->dice->init( "dice" );

	}

    /*
        setupNewGame:

        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame( $players, $options = array() )
    {
        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $gameinfos = self::getGameinfos();
        $default_colors = $gameinfos['player_colors'];

        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = array();
        foreach( $players as $player_id => $player )
        {
            $color = array_shift( $default_colors );
            $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes( $player['player_name'] )."','".addslashes( $player['player_avatar'] )."')";
        }
        $sql .= implode( ',', $values );
        self::DbQuery( $sql );
        self::reattributeColorsBasedOnPreferences( $players, $gameinfos['player_colors'] );
        self::reloadPlayersBasicInfos();

        /************ Start the game initialization *****/

        // Init global values with their initial values
        //self::setGameStateInitialValue( 'my_first_global_variable', 0 );

        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        //self::initStat( 'table', 'table_teststat1', 0 );    // Init a table statistics
        //self::initStat( 'player', 'player_teststat1', 0 );  // Init a player statistics (for all players)


        self::initStat( 'table', 'turns_number', 0 );    // Init a table statistics

        self::initStat( 'player', 'points_tiles', 0 );
        self::initStat( 'player', 'points_dev', 0 );
        self::initStat( 'player', 'points_chips', 0 );
        self::initStat( 'player', 'tile_count', 0 );
        self::initStat( 'player', 'dice_number', 5 );
        self::initStat( 'player', 'dice_rolled', 0 );
        self::initStat( 'player', 'dice_recruited', 0 );
        self::initStat( 'player', 'dice_used', 0 );
        self::initStat( 'player', 'dice_returned', 0 );
        self::initStat( 'player', 'dice_recall', 0 );
        self::initStat( 'player', 'dice_scout', 0 );
        self::initStat( 'player', 'dice_stock', 0 );
        self::initStat( 'player', 'dice_dev', 0 );
        self::initStat( 'player', 'dice_settle', 0 );
        self::initStat( 'player', 'dice_produce', 0 );
        self::initStat( 'player', 'dice_trade', 0 );
        self::initStat( 'player', 'dice_consume', 0 );

        // 12 vp chips per players
        self::setGameStateInitialValue( 'vp_stock', 12 * count( $players ) );

        // Init tiles /////

        // Normal tiles
        $tiles = array();
        for( $i=1; $i<=56; $i++ )
        {
            if( $i != 54 ) // 54 does not exists ...
            {
                $tiles[] = array(
                    'type' => $i,
                    'type_arg' => 0, // = side.
                    'nbr' => 1
                );
            }
        }
        $this->tiles->createCards( $tiles, 'deck' );
        $this->tiles->shuffle( 'deck' );

        // Home worlds
        $tiles = array();
        for( $i=160; $i<=168; $i++ )
        {
            $tiles[] = array(
                'type' => $i,
                'type_arg' => 0, // = side.
                'nbr' => 1
            );
        }
        $this->tiles->createCards( $tiles, 'homeworlds' );
        $this->tiles->shuffle( 'homeworlds' );

        // Faction tiles
        $tiles = array();
        for( $i=1001; $i<=1018; $i++ )
        {
            $tiles[] = array(
                'type' => $i,
                'type_arg' => 0, // = side.
                'nbr' => 1
            );
        }
        $this->tiles->createCards( $tiles, 'factiontiles' );
        $this->tiles->shuffle( 'factiontiles' );


        // Init dice
        foreach( $this->dice_types as $type_id => $dice_type )
        {
            $dice = array( array(
                'type' => $type_id,
                'type_arg' => 1,    // = dice face
                'nbr' => $dice_type['number']
            ) );
            $this->dice->createCards( $dice, 'deck'.$type_id );
        }

        // Give 1 homeworld to each player + 1 faction tile
        foreach( $players as $player_id => $player )
        {
            // 1 homeworld
            $this->tiles->pickCardForLocation( 'homeworlds', 'tableau', $player_id );

            // 1 faction tile
            $faction_tile = $this->tiles->pickCardForLocation( 'factiontiles', 'tableau', $player_id );
            // + give also the "sister" faction tile
            if( $faction_tile['type']%2 == 1 )
                $faction_tile_sister_type = $faction_tile['type'] + 1;
            else
                $faction_tile_sister_type = $faction_tile['type'] - 1;

            $sister_faction_tile = $this->tiles->getCardsOfTypeInLocation( $faction_tile_sister_type, null, 'factiontiles' );
            if( count( $sister_faction_tile ) != 1 )
                throw new SystemException( "Cannot find faction tile of type ".$faction_tile_sister_type );

            $sister_faction_tile = reset( $sister_faction_tile );

            $this->tiles->moveCard( $sister_faction_tile['id'], 'tableau', $player_id );

            // + 2 tiles on construction zone
            $tobuild = $this->tiles->pickCardForLocation( 'deck', 'bd'.$player_id );
            if( $this->tiles_types[ $tobuild['type'] ]['category'] != 'dev' )
            {
                // Flipping this tile to another type!
                $newtype = self::getFlipped( $tobuild['type'] );
                self::DbQuery( "UPDATE tile SET card_type='$newtype' WHERE card_id='".$tobuild['id']."' " );
                $tobuild['type'] = $newtype;
            }
            $current_dev_cost = $this->tiles_types[ $tobuild['type'] ]['cost'];
            $alternative_world_cost = $this->tiles_types[ self::getFlipped( $tobuild['type'] ) ]['cost'];

            $tobuild = $this->tiles->pickCardForLocation( 'deck', 'bw'.$player_id );
            if( $this->tiles_types[ $tobuild['type'] ]['category'] != 'world' )
            {
                // Flipping this tile to another type!
                $newtype = self::getFlipped( $tobuild['type'] );
                self::DbQuery( "UPDATE tile SET card_type='$newtype' WHERE card_id='".$tobuild['id']."' " );
                $tobuild['type'] = $newtype;
            }
            $current_world_cost = $this->tiles_types[ $tobuild['type'] ]['cost'];
            $alternative_dev_cost = $this->tiles_types[ self::getFlipped( $tobuild['type'] ) ]['cost'];

            $bShouldFlipStartingTile = false;

            if( $alternative_dev_cost < $current_dev_cost )
                $bShouldFlipStartingTile = true;
            else if( $alternative_dev_cost == $current_dev_cost )
            {
                if( $alternative_world_cost < $current_world_cost )
                    $bShouldFlipStartingTile = true;
            }

            if( $bShouldFlipStartingTile )
            {
                self::flipStartingTiles( $player_id );
            }

            // 3 home dice in cup
            $this->dice->pickCardsForLocation( 3, 'deck1', 'cup', $player_id );

            // 2 home dice in citizenry
            $this->dice->pickCardsForLocation( 2, 'deck1', 'citizenry', $player_id );
        }

        // Take dice given by homeworlds + other effects from home dice
        $this->applyInitialPowers();

        $this->initialScoreCompute();

        // Activate first player (which is in general a good idea :) )
        $this->activeNextPlayer();

        /************ End of the game initialization *****/
    }

    function applyInitialPowers()
    {
        $tiles = $this->tiles->getCardsInLocation( 'tableau' );

        foreach( $tiles as $tile )
        {
            self::applyEffect( $tile['location_arg'], $tile['type'], $tile['id'], null, true, true );
        }
    }

    function getFlipped( $type_id )
    {
        if( $type_id <= 56 )
        {
            return ( 100 + $type_id - 1 );
        }
        else if( $type_id <= 155 )
        {
            return ( $type_id - 99 );
        }
        else
            throw new SystemException( "Tried to flip tile $type_id which is NOT flipped" );
    }

    function initialScoreCompute()
    {
        $tableau = $this->tiles->getCardsInLocation( 'tableau' );

        foreach( $tableau as $tile )
        {
            $gain = $this->tiles_types[ $tile['type'] ]['cost'];
            $player_id = $tile['location_arg'];

            $this->bga->playerScore->inc($player_id, $gain);
        }

        self::notifyAllPlayers( 'initialScores', '', array(
            'scores' => self::getCollectionFromDB( "SELECT player_id, player_score FROM player", true )
        ) );
    }

    /*
        getAllDatas:

        Gather all informations about current game situation (visible by the current player).

        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas()
    {
        $result = array();

        $current_player_id = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!

        $players = self::loadPlayersBasicInfos();

        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_score score, player_choosed_phase, player_credit credit, player_vp_chip vp_chip FROM player ";
        $result['players'] = self::getCollectionFromDb( $sql );

        $result['tiles_types'] = $this->tiles_types;
        $result['dicecup'] = $this->dice->getCardsInLocation( 'cup' );
        $result['dicecitizenry'] = $this->dice->getCardsInLocation( 'citizenry' );
        $result['dicephase'] = $this->dice->getCardsInLocation( array( 'phase1','phase2','phase3','phase4','phase5', 'phase6' ) );
        $result['devdice']=$this->dice->getCardsInLocation( 'devconstruct' );
        $result['worlddice'] = $this->dice->getCardsInLocation( 'worldconstruct' );
        $result['resources'] = $this->dice->getCardsInLocation( 'resource' );
        $result['dice_types'] = $this->dice_types;
        $result['vp_stock'] = self::getGameStateValue( 'vp_stock' );

        $result['tableau'] = $this->tiles->getCardsInLocation( 'tableau' );
        $result['scouted'] = self::toDoubleSidedTiles( $this->tiles->getCardsInLocation( 'scout', $current_player_id ) );
        $result['colors'] = $this->colors;
        $result['dice_faces'] = $this->dice_faces;

        $result['selectedphases'] = self::number_to_phases_to_active( self::getGameStateValue('selectedphases') );

        $bd = array();
        $wd = array();
        foreach( $players as $player_id => $player )
        {
            $bd[] = 'bd'.$player_id;
            $wd[] = 'bw'.$player_id;
        }
        $result['builddev'] = $this->tiles->getCardsInLocation( $bd, null, 'card_location_arg' );
        $result['buildworld'] = $this->tiles->getCardsInLocation( $wd, null, 'card_location_arg' );

        // Only keep card on top of opponent's locations
        $player_to_top = array();
        foreach( $result['builddev'] as $tile )
        {
            if( $tile['location'] != 'bd'.$current_player_id )
            {
                if( ! isset( $player_to_top[ $tile['location'] ] ) )
                    $player_to_top[ $tile['location'] ] = $tile['location_arg'];
                else
                    $player_to_top[ $tile['location'] ] = max( $tile['location_arg'], $player_to_top[ $tile['location'] ] );
            }
        }
        foreach( $result['builddev'] as $i =>  $tile )
        {
            if( $tile['location'] != 'bd'.$current_player_id )
            {
                if( $tile['location_arg'] != $player_to_top[ $tile['location'] ] )
                    $result['builddev'][$i]['type'] = '1'; // So that the information is unuseful
            }
        }

        // Only keep card on top of opponent's locations
        $player_to_top = array();
        foreach( $result['buildworld'] as $tile )
        {
            if( $tile['location'] != 'bw'.$current_player_id )
            {
                if( ! isset( $player_to_top[ $tile['location'] ] ) )
                    $player_to_top[ $tile['location'] ] = $tile['location_arg'];
                else
                    $player_to_top[ $tile['location'] ] = max( $tile['location_arg'], $player_to_top[ $tile['location'] ] );
            }
        }
        foreach( $result['buildworld'] as $i =>  $tile )
        {
            if( $tile['location'] != 'bw'.$current_player_id )
            {
                if( $tile['location_arg'] != $player_to_top[ $tile['location'] ] )
                    $result['buildworld'][$i]['type'] = '1'; // So that the information is unuseful
            }
        }


        $states = $this->gamestate->getCurrentMainState()->toArray();
        if( $states['name'] == "assign" )
        {
            // exception: during this state, do not communicate the phase dice except current player
            foreach( $result['dicephase'] as $i => $die )
            {
                if( $die['location_arg'] != $current_player_id )
                    $result['dicephase'][$i]['type_arg'] = 'X';   // Value is masked
            }
        }

        return $result;
    }

    function toDoubleSidedTiles( $tiles )
    {
        $result = array(
            'dev' => array(),
            'world'=> array()
        );

        foreach( $tiles as $tile )
        {
            if( $this->tiles_types[$tile['type']]['category'] == 'dev' )
            {
                $result['dev'][] = $tile;
            }
            else
            {
                $result['world'][] = $tile;
            }

            $tile['type'] = self::getFlipped( $tile['type'] );

            if( $this->tiles_types[$tile['type']]['category'] == 'dev' )
            {
                $result['dev'][] = $tile;
            }
            else
            {
                $result['world'][] = $tile;
            }

        }

        return $result;

    }

    /*
        getGameProgression:

        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).

        This method is called each time we are in a game state with the "updateGameProgression" property set to true
        (see states.inc.php)
    */
    function getGameProgression()
    {
        $remainingVp = self::getGameStateValue( "vp_stock" );
        $tableau_count = $this->tiles->countCardsByLocationArgs( 'tableau' );


        $initialVp = max( 1, count( $tableau_count ) * 12 );

        $vpProgression = round( ( $initialVp-$remainingVp ) * 100 / $initialVp );
        $vpProgression = min( $vpProgression, 100 );
        $tableauProgression = 0;
        foreach( $tableau_count as $count )
        {
            $progression = round( ($count-1)*100/11 );   // Note: 1 card initial, and go to 12 cards
            $progression = min( $progression, 100 );
            $tableauProgression = max( $tableauProgression, $progression );
        }

        return max( $tableauProgression, $vpProgression );
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////

    /*
        In this space, you can put any utility methods useful for your game logic
    */

    // "Consume" a die from given player / give phase
    function consumeDie( $player_id, $phase_id, $prioritydie = null )
    {
        $dice = $this->dice->getCardsInLocation( 'phase'.$phase_id, $player_id );

        if( count( $dice ) == 0 )
            throw new SystemException( "No more die for this phase" );

        $die_to_use = null;

        // Consume in priority 1 chosen die
        if( $prioritydie !== null && $prioritydie != 0 )
        {
            foreach( $dice as $die )
            {
                if( $die['id'] == $prioritydie )
                    $die_to_use = $die;
            }
        }

        // Consume in priority 2 temporary dice (type_arg = 7)
        if( $die_to_use === null )
        {
            foreach( $dice as $die )
            {
                if( $die['type_arg'] == 7 )
                    $die_to_use = $die;
            }
        }

        // Consume first die otherwise
        if( $die_to_use === null )
            $die_to_use = reset( $dice );

        if( $die_to_use['type_arg'] != 7 )
        {
            // Okay, moving this die to citizenry
            $this->dice->moveCard( $die_to_use['id'], 'citizenry', $player_id );

            self::notifyAllPlayers( 'consumeDie', '', array(
                'player_id' => $player_id,
                'die' => $die_to_use
            ) );
        }
        else
        {
            // Remove this die
            self::DbQuery( "DELETE FROM dice WHERE card_id='".$die_to_use['id']."'" );

            self::notifyAllPlayers( 'removedie', '', array(
                'player_id' => $player_id,
                'die' => $die_to_use,
                'location' => 'phase',
                'phase' => $phase_id
            ) );

        }

        return $die_to_use;
    }

    function returnUnusedDiceToCup( $phase_id )
    {
        $dice = $this->dice->getCardsInLocation( 'phase'.$phase_id );

        foreach( $dice as $die )
        {
            $player_id = $die['location_arg'];

            if( $die['type_arg'] != 7 )
            {
                $this->dice->moveCard( $die['id'], 'cup', $player_id );

                self::notifyAllPlayers( 'returnedDie', '', array(
                    'player_id' => $player_id,
                    'die' => $die
                ) );

                self::incStat( 1, 'dice_returned', $player_id );
            }
            else
            {
                // Remove this die
                self::DbQuery( "DELETE FROM dice WHERE card_id='".$die['id']."'" );

                self::notifyAllPlayers( 'removedie', '', array(
                    'player_id' => $player_id,
                    'die' => $die,
                    'location' => 'phase',
                    'phase' => $phase_id
                ) );

            }
        }
    }

    function inactivePlayerIfNoMoreDie( $player_id, $phase_id )
    {
        $dice = $this->dice->getCardsInLocation( 'phase'.$phase_id, $player_id );

        if( count( $dice ) == 0 )
        {
            // During Explore phase (phase 1), keep players with Advanced Logistics active
            // so they can still rearrange tiles after using their last die
            // However during handling of Alien Research Ship we mustn't end up in a loop.
            if( $phase_id == 1 && !self::checkAction( 'alien_research', false ) )
            {
                $advanced_logistics_tiles = self::getTilesWithEffects( 'explore_reassign', $player_id );
                if( count( $advanced_logistics_tiles ) > 0 )
                {
                    // Player has Advanced Logistics - keep them active, they need to click "Done" manually
                    return;
                }
            }

            $state = $this->gamestate->getCurrentMainState()->toArray();
            if( $state['type'] == 'activeplayer' )
                $this->gamestate->nextState( 'no_more_actions' );
            else
                $this->gamestate->setPlayerNonMultiactive( $player_id, "no_more_actions" );
        }
    }

    function chooseComboFlip()
    {
        self::checkAction( 'startingWorldCombination' );

        $player_id = self::getCurrentPlayerId();

        $notif = self::flipStartingTiles( $player_id );


        self::notifyAllPlayers( 'chooseComboFlip', '', $notif );
    }

    function flipStartingTiles( $player_id )
    {
        $dev_to_built = $this->tiles->getCardsInLocation( 'bd'.$player_id );
        $dev_to_built = reset( $dev_to_built );
        $world_to_built = $this->tiles->getCardsInLocation( 'bw'.$player_id );
        $world_to_built = reset( $world_to_built );

        $dev_to_world_type = self::getFlipped( $dev_to_built['type'] );
        $world_to_dev_type = self::getFlipped( $world_to_built['type'] );

        $world_to_built['type'] = $world_to_dev_type;
        $world_to_built['location'] = 'bd'.$player_id;
        $dev_to_built['type'] = $dev_to_world_type;
        $dev_to_built['location'] = 'bw'.$player_id;

        self::DbQuery( "UPDATE tile SET card_type='$dev_to_world_type', card_location='bw$player_id' WHERE card_id='".$dev_to_built['id']."' " );
        self::DbQuery( "UPDATE tile SET card_type='$world_to_dev_type', card_location='bd$player_id' WHERE card_id='".$world_to_built['id']."' " );

        return array(
            'player_id' => $player_id,
            'bd' => $world_to_built,
            'bw' => $dev_to_built
        );
    }





//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in rollforthegalaxy.action.php)
    */

    function doneChooseCombo()
    {
        self::checkAction( 'startingWorldCombination' );

        $player_id = self::getCurrentPlayerId();

        $this->gamestate->setPlayerNonMultiactive( $player_id, "startingWorldCombination" );
    }

    function doneAssign()
    {
        self::checkAction( 'assign' );

        // Check player activate at least one phase
        $player_id = self::getCurrentPlayerId();

        if( self::getUniqueValueFromDB( "SELECT player_choosed_phase FROM player WHERE player_id='$player_id'" ) === null )
            throw new UserException( self::_("You must selects a phase by placing a worker (die) anywhere on your phase strip.") );

        // Check that we have no Executive power half used
        if( self::getUniqueValueFromDB( "SELECT COUNT( card_id ) FROM tile WHERE card_location='tableau' AND card_location_arg='$player_id' AND card_type='132' AND card_type_arg>0 AND card_type_arg<10" ) > 0 )
            throw new UserException( self::_("Executive Power: you have to reassign EXACTLY TWO dice. Please use Reset dice if you cannot.") );

        $this->gamestate->setPlayerNonMultiactive( $player_id, "assign" );
    }

    function resetAssign()
    {
            // So the reset is possible, even for inactive players
        $this->gamestate->checkPossibleAction('assign');

        $player_id = self::getCurrentPlayerId();

        $this->gamestate->setPlayersMultiactive( array( $player_id ), 'dummy' );

        self::DbQuery( "UPDATE dice SET card_location='cup' WHERE card_location_arg='$player_id' AND card_location IN ('phase1', 'phase2', 'phase3', 'phase4', 'phase5' )" );
        self::DbQuery( "UPDATE player SET player_dictate='0' WHERE player_id='$player_id'" );
        self::DbQuery( "UPDATE tile SET card_type_arg='0' WHERE card_location='tableau' AND card_location_arg='$player_id'" );

        // Replace all dice to their original positions
        self::autoAssignDice( $player_id );

        $dice = array_merge( $this->dice->getCardsInLocation( 'phase1', $player_id ), $this->dice->getCardsInLocation( 'phase2', $player_id ), $this->dice->getCardsInLocation( 'phase3', $player_id ), $this->dice->getCardsInLocation( 'phase4', $player_id ), $this->dice->getCardsInLocation( 'phase5', $player_id ), $this->dice->getCardsInLocation( 'phase6', $player_id ) );

        $players = array();
        $players[ $player_id ] = true;

        self::notifyPlayer( $player_id, 'dicerolled_nowait', '', array(
            'dice' => $dice,
            'players' => $players,
            'available' => $assignpowers = self::getAvailableAssigned( $player_id )
        ) );


    }

    function dictate( $die_id )
    {
        self::checkAction( 'assign' );

        $player_id = self::getCurrentPlayerId();

        $current_selection_die = self::getUniqueValueFromDB( "SELECT player_choosed_phase FROM player WHERE player_id='$player_id'" );

        if( $current_selection_die === null )
            throw new UserException( self::_("You must start by selecting a phase to activate (by moving a dice to the black phase strip at the top).") );


        if( self::getUniqueValueFromDB( "SELECT player_dictate FROM player WHERE player_id='$player_id'" ) != 0 )
            throw new SystemException( "Dictate already used" );

        $die = $this->dice->getCard( $die_id );

        $bWasSelection = false;

        if( substr( $die['location'], 0, 5 ) != 'phase' )
            throw new SystemException( "This die is not yours" );
        if( $die['location_arg'] != $player_id )
            throw new SystemException( "This die is not yours" );


        if( $current_selection_die == $die_id )
        {
            self::DbQuery( "UPDATE player SET player_choosed_phase=NULL WHERE player_id='$player_id'" );
        }

        $this->dice->moveCard( $die_id, 'cup', $player_id );

        $triple_dictate = self::getTilesWithEffects( 'three_on_dictate', $player_id );
        $bTripleDictate = ( count( $triple_dictate ) > 0 );

        self::notifyPlayer( $player_id, 'returnedDie', '' , array(
                   'player_id' => $player_id,
                    'die' => $die,
                    'dictate' => true,
                    'triple' => $bTripleDictate
        ) );

        if( $bTripleDictate == false )
            self::DbQuery( "UPDATE player SET player_dictate='1' WHERE player_id='$player_id'" );
        else
            self::DbQuery( "UPDATE player SET player_dictate='11' WHERE player_id='$player_id'" );
    }

    function reassign( $die_id, $phase_id, $bActivate, $power_tile_id = null )
    {
        self::checkAction( 'assign' );

        $player_id = self::getCurrentPlayerId();
        $die = $this->dice->getCard( $die_id );

        $bWasSelection = false;

        if( substr( $die['location'], 0, 5 ) != 'phase' )
            throw new SystemException( "This die is not yours" );
        if( $die['location_arg'] != $player_id )
            throw new SystemException( "This die is not yours" );

        $power_used = null;
        $power_tile = null;

        $current_selection_die = self::getUniqueValueFromDB( "SELECT player_choosed_phase FROM player WHERE player_id='$player_id'" );

        if( $current_selection_die === null && ! $bActivate )
            throw new UserException( self::_("You must start by selecting a phase to activate (by moving a dice to the black phase strip at the top).") );


        if( $bActivate )
        {
            if( $current_selection_die === null )
            {
                // Okay, didn't choose any phase yet. Let's choose this one.
                self::DbQuery( "UPDATE player SET player_choosed_phase='$die_id' WHERE player_id='$player_id'" );
            }
            else if( $current_selection_die == $die_id )
            {
                // Change current die selection place => ok!
                $bWasSelection = true;

                $reassigned_already_used = self::getUniqueValueFromDB( "SELECT card_type FROM tile WHERE card_type IN ('31','43','132') AND card_location='tableau' AND card_location_arg='$player_id' AND card_type_arg!='0' LIMIT 1" );
                if( $reassigned_already_used !== null )
                    throw new UserException( sprintf( self::_('You cannot change selected phase after using %s. Please reset dice if you really want to do this.'), $this->tiles_types[ $reassigned_already_used ]['name'] ) );
            }
            else
            {
                throw new UserException( self::_("You already choosed a phase to activate with a die.") );
            }

            $power_used = clienttranslate('phase selection');
        }
        else
        {
            // Check if the die can be reassign

            if( $die['location'] == 'phase'.$phase_id && ( $die_id != $current_selection_die ))
                throw new SystemException('You try to move a die to the phase it is alreay assigned' );


            if( $die_id == $current_selection_die )
            {
                // UPDATE: cannot do this
                throw new UserException( self::_('The only way to do this is to use Reset dice button.') );
            }

            if( $power_used === null )
            {
                if( $this->dice_types[ $die['type'] ]['faces'][ $die['type_arg']-1 ] == 6 ) // Joker?
                    $power_used = clienttranslate('joker die');
            }

            if( $power_used === null )
            {
                $dictate_status = self::getUniqueValueFromDB( "SELECT player_dictate FROM player WHERE player_id='$player_id'" );
                if( $dictate_status == 1 || $dictate_status == 11 || $dictate_status == 12 || $dictate_status == 13 )
                {
                    // Hey, we have a dictate! So Use it :)
                    $power_used = clienttranslate( 'dictate' );

                    if( $dictate_status == 1 || $dictate_status == 13 )
                    {
                        $infinite_dictate = self::getTilesWithEffects( 'dictate', $player_id );
                        if( count( $infinite_dictate ) > 0 )
                        {
                            self::DbQuery( "UPDATE player SET player_dictate=0 WHERE player_id='$player_id'" );  // Can be use infinitely
                        }
                        else
                            self::DbQuery( "UPDATE player SET player_dictate=2 WHERE player_id='$player_id'" );  // Cannot be used anymore
                    }
                    else
                        self::DbQuery( "UPDATE player SET player_dictate=player_dictate+1 WHERE player_id='$player_id'" );
                }
            }

            // Get other reassign powers
            if( $power_used === null )
            {
                $tiles = self::getTilesWithEffects( 'reassign', $player_id );

                foreach( $tiles as $tile )
                {
                    if( $power_used === null )
                    {
                        $bCanBeUsed = true;

                        if( $tile['type_arg'] != 10 )    // (note: if not used yet)
                        {
                            // Check conditions

                            if( isset( $tile['effect']['to'] ) )
                            {
                                if( $tile['effect']['to'] == 'current' )
                                {
                                    if( $current_selection_die === null )
                                        $bCanBeUsed = false;
                                    else
                                    {
                                        $selected_phase = self::getUniqueValueFromDB( "SELECT card_location FROM dice WHERE card_id='$current_selection_die'" );
                                        $selected_phase = substr( $selected_phase, 5 );

                                        if( $selected_phase != $phase_id )
                                            $bCanBeUsed = false;
                                    }
                                }
                                else
                                {
                                    if( ! in_array( $phase_id, $tile['effect']['to'] ) )
                                        $bCanBeUsed = false;
                                }
                            }

                            if( isset( $tile['effect']['color'] ) )
                            {
                                if( $tile['effect']['color'] == 'nonwhite' )
                                {
                                    if( $die['type'] == 1 )
                                        $bCanBeUsed = false;
                                }
                                else
                                {
                                    if( ! in_array( ( $die['type'] ), $tile['effect']['color'] ) )
                                        $bCanBeUsed = false;
                                }
                            }

                            if( isset( $tile['effect']['from'] ) )
                            {
                                if( $die['location'] != 'phase1' )
                                    $bCanBeUsed = false;
                            }


                            if( $power_tile_id !== null && $power_tile_id == $tile['id'] )
                            {
                                // Player said that he'd like to use THIS power for this reassign

                                if( $bCanBeUsed )
                                {
                                    // Fine!
                                }
                                else
                                {
                                    throw new UserException( sprintf( _("Sorry, but the power of %s does not apply to this reassign."), _( $this->tiles_types[ $tile['type'] ]['name'] ) ) );
                                }
                            }
                            else if( $power_tile_id !== null && $power_tile_id != $tile['id'] && $bCanBeUsed )
                            {
                                // We should skip this one (non selected by player)
                                $bCanBeUsed = false;
                            }


                            if( $bCanBeUsed )
                            {
                                // We can use it!
                                $power_used = $this->tiles_types[ $tile['type'] ]['name'];
                                $power_tile = $tile['id'];

                                $used_nbr = $tile['type_arg'];
                                $used_nbr ++;

                                if( ! isset( $tile['effect']['nbr'] ) )
                                    $tile['effect']['nbr'] = 1;

                                if( $tile['type'] == 132 ) // Executive power, very particular (must move exactly 2 die from same phase)
                                {
                                    $die_is_from = substr( $die['location'], 5 );
                                    if( $tile['type_arg'] == 0 )
                                    {
                                        // This is the first time we are using Executive power => no problem (but store the ID of the die phase)
                                        self::DbQuery( "UPDATE tile SET card_type_arg='$die_is_from' WHERE card_id='".$tile['id']."'" );
                                        $power_tile = null; // => so the tile is not unselected
                                    }
                                    else
                                    {
                                        if( $die_is_from != $tile['type_arg'] )
                                        {
                                            throw new UserException( sprintf( self::_("Executive Power reassign power: the second die must come from the same phase (%s) than the first one."), $this->dice_faces[ $tile['type_arg'] ] ) );
                                        }

                                        self::DbQuery( "UPDATE tile SET card_type_arg='10' WHERE card_id='".$tile['id']."'" ); // Cannot be used anymore
                                    }
                                }
                                else
                                {
                                    // Standard behaviour

                                    if( $used_nbr >= $tile['effect']['nbr'] )
                                        self::DbQuery( "UPDATE tile SET card_type_arg='10' WHERE card_id='".$tile['id']."'" ); // Cannot be used anymore
                                    else
                                    {
                                        self::DbQuery( "UPDATE tile SET card_type_arg='$used_nbr' WHERE card_id='".$tile['id']."'" ); // Cannot be used anymore
                                        $power_tile = null; // => so the tile is not unselected
                                    }
                                }
                            }

                        }

                    }
                }
            }

            if( $power_used === null )
                throw new UserException( self::_("You have no dice re-assign power that can do this (or already used it)") );

            // OKAY, we can do this!
        }

        // Reassign this die
        $this->dice->moveCard( $die_id, 'phase'.$phase_id, $player_id );

        self::notifyPlayer( $player_id, 'movePhaseDie',_('${type} die reassigned to phase ${phasename} (using ${reason})'), array(
            'i18n' => array( 'phasename', 'reason', 'type' ),
            'die' => $die,
            'type' => $this->dice_types[ $die['type'] ]['name'],
            'phase' => $phase_id,
            'phasename' => $this->dice_faces[ $phase_id ],
            'activate' => $bActivate,
            'wasselection' => $bWasSelection,
            'power' => $power_used,
            'powertile' => $power_tile,
            'reason' => $power_used
        ) );
    }


    function stock( $prioritydie )
    {
        self::checkAction( 'stock' );

        $player_id = self::getCurrentPlayerId();

        if( $this->tiles->countCardInLocation( 'explorediscard', $player_id ) > 0 )
            throw new UserException( self::_("You discarded some tiles before, so you have to do a Scout action now.") );

        if( $this->tiles->countCardInLocation( 'scout', $player_id ) > 0 )
            throw new UserException( self::_("You must choose the side of already scouted tiles.") );

        $die = self::consumeDie( $player_id, 1, $prioritydie );

        self::incStat( 1, 'dice_stock', $player_id );
        self::incStat( 1, 'dice_used', $player_id );


        self::DbQuery( "UPDATE player SET player_credit = LEAST( 10, player_credit+2 ) WHERE player_id='$player_id'" );
        $new_credit = self::getUniqueValueFromDB( "SELECT player_credit FROM player WHERE player_id='$player_id'" );

        self::notifyAllPlayers( "updateCredit", clienttranslate('Stock: ${player_name} gets +2$.'), array(
            'player_id' => $player_id,
            'player_name' => self::getCurrentPlayerName(),
            'credit' => $new_credit
        ) );

        if( $die['type'] == 7 )
        {
            // Alien explorer
            $tiles = self::getTilesWithEffects( 'explore_bonus_doubled', $player_id );

            foreach( $tiles as $tile )
            {
                self::DbQuery( "UPDATE player SET player_credit = LEAST( 10, player_credit+2 ) WHERE player_id='$player_id'" );
                $new_credit = self::getUniqueValueFromDB( "SELECT player_credit FROM player WHERE player_id='$player_id'" );

                self::notifyAllPlayers( "updateCredit", clienttranslate('Alien Archeology: ${player_name} gets an additional +2$.'), array(
                    'player_id' => $player_id,
                    'player_name' => self::getCurrentPlayerName(),
                    'credit' => $new_credit
                ) );
            }
        }

        self::inactivePlayerIfNoMoreDie( $player_id, 1 );
    }

    function scoutdiscard( $cards )
    {
        self::checkAction( 'stock');

        $player_id = self::getCurrentPlayerId();

        // Cannot discard tiles if you have no dice left to scout with
        $dice = $this->dice->getCardsInLocation( 'phase1', $player_id );
        if( count( $dice ) == 0 )
            throw new UserException( self::_("You cannot discard tiles because you have no dice left to scout with."), true );

        $tiles = $this->tiles->getCards( $cards );

        $dev_cards = array();
        $world_cards = array();

        foreach( $tiles as $tile )
        {
            if( $tile['location'] == 'bd'.$player_id )
                $dev_cards[] = $tile['id'];
            else if( $tile['location'] == 'bw'.$player_id )
                $world_cards[] = $tile['id'];
            else
                throw new SystemException( "This tile is not in your construction zone" );
        }

        $this->tiles->moveCards( $cards, 'explorediscard', $player_id );

        self::notifyAllPlayers( 'scoutdiscard', clienttranslate('${player_name} discard ${nbr} card from construction zone to pick ${nbr} more card on scout action.'), array(
            'player_name' => self::getCurrentPlayerName(),
            'player_id' => $player_id,
            'dev_cards' => $dev_cards,
            'world_cards' => $world_cards,
            'dev_top' => $this->tiles->getCardOnTop( 'bd'.$player_id ),
            'world_top' => $this->tiles->getCardOnTop( 'bw'.$player_id ),
            'nbr' => count( $tiles )
        ) );
    }

    function advancedlogistics( $tile_id, $action )
    {
        self::checkAction( 'advancedlogistics' );

        $player_id = self::getCurrentPlayerId();

        $tiles = self::getTilesWithEffects( 'explore_reassign', $player_id );
        if( count( $tiles ) == 0)
            throw new SystemException( "Cannot find Advanced Logistics" );

        $tile = $this->tiles->getCard( $tile_id );

        if( $tile['location'] != 'bd'.$player_id && $tile['location'] != 'bw'.$player_id )
            throw new SystemException( "This tile is not in your construction zone" );

        if( $tile['location'] == 'bd'.$player_id )
            $zone = 'dev';
        else
            $zone = 'world';

        $private_tiles = array(
            'dev' => null,
            'world' => null,
        );

        if( $action == 'top' )
        {
            $this->tiles->insertCardOnExtremePosition( $tile_id, $tile['location'], true );
            $private_tiles[$zone] = $this->tiles->getCardsInLocation( $tile['location'], null, 'location_arg' );
        }
        else if( $action == 'bot' )
        {
            $this->tiles->insertCardOnExtremePosition( $tile_id, $tile['location'], false );
            $private_tiles[$zone] = $this->tiles->getCardsInLocation( $tile['location'], null, 'location_arg' );
        }
        else if( $action == 'flip' )
        {
            if( $zone == 'dev' )
            {
                $dev_to_world_type = self::getFlipped( $tile['type'] );
                self::DbQuery( "UPDATE tile SET card_type='$dev_to_world_type', card_location='bw$player_id' WHERE card_id='$tile_id' " );
            }
            else
            {
                $world_to_dev_type = self::getFlipped( $tile['type'] );
                self::DbQuery( "UPDATE tile SET card_type='$world_to_dev_type', card_location='bd$player_id' WHERE card_id='$tile_id' " );
            }

            $private_tiles['dev'] = $this->tiles->getCardsInLocation( 'bd'.$player_id, null, 'location_arg' );
            $private_tiles['world'] = $this->tiles->getCardsInLocation( 'bw'.$player_id, null, 'location_arg' );
        }

        $public_tiles = $private_tiles;
        foreach (['dev', 'world'] as $z) {
            if ($public_tiles[$z] != null) {
                for ($i = 0; $i < count($public_tiles[$z]) - 1; $i++) {
                    // Use Secluded World as dummy
                    $public_tiles[$z][$i]['type'] = 1;
                }
            }
        }

        $this->bga->notify->all('resetConstruction', clienttranslate('${player_name} uses Advanced Logistics to reorder his construction zone.'), array(
            'player_id' => $player_id,
            'player_name' => self::getCurrentPlayerName(),
            'dev_tiles' => $public_tiles['dev'],
            'world_tiles' => $public_tiles['world'],
            '_private' => [
                $player_id => [
                    'my_dev_tiles' => $private_tiles['dev'],
                    'my_world_tiles' => $private_tiles['world'],
                ]
            ],
        ));
    }


    function scout( $prioritydie )
    {
        self::checkAction( 'stock' );


        $player_id = self::getCurrentPlayerId();

        if( $this->tiles->countCardInLocation( 'scout', $player_id ) > 0 )
            throw new UserException( self::_("You must choose the side of already scouted tiles.") );



        $die = self::consumeDie( $player_id, 1, $prioritydie );

        self::incStat( 1, 'dice_scout', $player_id );
        self::incStat( 1, 'dice_used', $player_id );

        $nbr = 1 + $this->tiles->countCardInLocation( 'explorediscard', $player_id );

        $tiles = self::getTilesWithEffects( 'explorekeep', $player_id );

        // This is NOT the power of Alien Research team
//        $nbr += count( $tiles );

        if( count( $tiles )> 0 && $die['type'] == 7 && self::getGameStateValue( 'saved_dice_nbr' ) == 0 )
        {
            $gain = 1;
            self::DbQuery( "UPDATE player SET player_credit = LEAST( 10, player_credit+$gain ) WHERE player_id='$player_id'" );
            $new_credit = self::getUniqueValueFromDB( "SELECT player_credit FROM player WHERE player_id='$player_id'" );

            $tile = reset( $tiles );

            self::notifyAllPlayers( "updateCredit", clienttranslate('${card_name}: ${player_name} gets +${nbr}$.'), array(
                'i18n' => array( 'card_name' ),
                'card_name' => $this->tiles_types[ $tile['type'] ]['name'],
                'player_id' => $player_id,
                'player_name' => self::getCurrentPlayerName(),
                'nbr' => $gain,
                'credit' => $new_credit
            ) );

            self::setGameStateValue( 'saved_dice_nbr',1 );

        }

        $this->tiles->moveAllCardsInLocation( 'explorediscard', 'usedexplore', $player_id );

        // Pick a new tile (+ other face)
        $scouted = $this->tiles->pickCardsForLocation( $nbr, 'deck', 'scout', $player_id );

        if( count( $scouted ) < $nbr )
        {
            // There is not enough in the bag!
            self::notifyAllPlayers( 'simpleNote', clienttranslate('There is not enough tiles in the bag: we are putting back into bag tiles under Explore phase tile.'), array() );

            $this->tiles->moveAllCardsInLocation( 'usedexplore', 'deck' );
            $this->tiles->shuffle( 'deck' );

            $more_scouted = $this->tiles->pickCardsForLocation( $nbr - count( $scouted ), 'deck', 'scout', $player_id );

            foreach( $more_scouted as $tile )
            {
                $scouted[] = $tile;
            }

            if( count( $scouted ) < $nbr )
            {
                self::notifyAllPlayers( 'simpleNote', "WARNING: still not enough tiles in the bag :(", array() );
            }
        }


        if( count( $scouted ) == 0 )
        {
            self::notifyAllPlayers( 'simpleNote', 'WARNING: Converting ${player_name} scouting to stock because of missing tiles :(', array('player_name'=> self::getCurrentPlayerName()) );
            self::DbQuery( "UPDATE player SET player_credit = LEAST( 10, player_credit+2 ) WHERE player_id='$player_id'" );
            $new_credit = self::getUniqueValueFromDB( "SELECT player_credit FROM player WHERE player_id='$player_id'" );
            self::notifyAllPlayers( "updateCredit", clienttranslate('Stock: ${player_name} gets +2$.'), array(
                'player_id' => $player_id,
                'player_name' => self::getCurrentPlayerName(),
                'credit' => $new_credit
            ) );
            self::setGameStateValue( 'saved_dice_nbr', 0 );
            self::inactivePlayerIfNoMoreDie( $player_id, 1 );
        } else {
            self::notifyAllPlayers( 'simpleNote', clienttranslate('Scout: ${player_name} picks ${nbr} tiles.'), array('player_name'=> self::getCurrentPlayerName(), 'nbr' => count( $scouted ) ) );
            self::notifyPlayer( $player_id, 'scouted', '', array(
                'tiles' => self::toDoubleSidedTiles( $scouted )
            ) );
        }
    }

    function pickScoutedTile( $tile_id, $side, $bOnTop )
    {
        self::checkAction( 'scout' );

        $tile = $this->tiles->getCard( $tile_id );
        $player_id = self::getCurrentPlayerId();

        if( $tile['location'] != 'scout' || $tile['location_arg'] != $player_id )
            throw new SystemException("This tile is not in your scouted area");

        if( $bOnTop )
        {
            // Check there is improved reconnaissace
            $tiles = self::getTilesWithEffects( 'explore_may_place_on_top', $player_id );
            if( count( $tiles ) == 0 )
                throw new SystemException( "Could not find improved reconnaissance" );

        }

        // Okay, I can scout this one
        if( $side != $this->tiles_types[ $tile['type'] ]['category'] )
        {
            // Must flip this tile
            $newtype = self::getFlipped( $tile['type'] );
            self::DbQuery( "UPDATE tile SET card_type='$newtype' WHERE card_id='".$tile['id']."' " );
            $tile['type'] = $newtype;
        }

        // Add it to construction zone
        $target_location = ( $side == 'dev' ) ? 'bd'.$player_id :'bw'.$player_id;
        $this->tiles->insertCardOnExtremePosition( $tile_id, $target_location , $bOnTop );

        self::notifyPlayer( $player_id, 'pickScouted', '', array(
            'tile' => $tile,
            'target' => $side,
            'top' => $bOnTop
        ) );

        $msg_tile = $tile;
        if( $bOnTop || $this->tiles->countCardInLocation( $target_location ) == 1 )
        {
            // The tile has been placed at the top of the stack => visible
            // do nothing
        }
        else
        {
            // The tile has been placed at the bottom of the stack => non visible.
            // Use Secluded World as dummy
            $msg_tile['type'] = 1;
        }

        self::notifyAllPlayers( 'newConstruction', clienttranslate('${player_name} picks a ${type}.'), array(
            'i18n' => array( 'type' ),
            'player_name' => self::getCurrentPlayerName(),
            'type' => $side == 'world' ? clienttranslate('world'): clienttranslate('development'),
            'target' => $side,
            'tile' => $msg_tile,
            'player_id' => $player_id,
            'top' => $bOnTop,
        ) );

        if( $this->tiles->countCardInLocation( 'scout', $player_id ) > 0 )
        {
            // Still some tiles to choose
        }
        else
            self::inactivePlayerIfNoMoreDie( $player_id, 1 );
    }

    function savedie( $die_id, $zone )
    {
        self::checkAction( 'savedie' );

        $player_id = self::getCurrentPlayerId();

        // Is this die on devconstruct?
        $die = $this->dice->getCard( $die_id );

        if( $zone == 'dev' )
        {
            if( $die['location'] != 'devconstruct' || $die['location_arg'] != $player_id )
                throw new SystemException( "This die is not in your construction zone" );
        }
        else
        {
            if( $die['location'] != 'worldconstruct' || $die['location_arg'] != $player_id )
                throw new SystemException( "This die is not in your construction zone" );
        }

        // Place die on cup
        $this->dice->moveCard( $die_id, 'cup', $player_id );

        self::incGameStateValue( 'saved_dice_nbr', 1 );

        self::notifyAllPlayers( "savedie", clienttranslate('${card_name}: ${player_name} places a ${diename} die in his cup.'), array(
            'i18n' => array( 'diename' ),
            'player_name' => self::getActivePlayerName(),
            'die' => $die,
            'player_id' => $player_id,
            'zone' =>  $zone,
            'diename' => $this->dice_types[ $die['type'] ]['name'],
            'card_name' => $this->tiles_types[ 102 ]['name']
        ) );

        $this->gamestate->nextState( 'continue' );
    }

    function autorecruit( $player_id, $bForce=false )
    {
        // For this player, autorecruit remaining dice in one of these 2 situations:
        // 1. there are enough credits to recruit all die
        // 2. all remaining dice are the same

        $dice = $this->dice->getCardsInLocation( 'citizenry', $player_id );
        $credit = self::getUniqueValueFromDB( "SELECT player_credit FROM player WHERE player_id='$player_id'" );

        if( $credit == 0 )
            return ;

        if( $credit >= count( $dice ) || $bForce )
        {
            // Recruit all dice
            foreach( $dice as $die )
            {
                $this->doRecruit( $player_id, $die['id'] );
                $credit --;

                if( $credit == 0 )
                    return ;
            }
        }
        else
        {
            $previous_type = null;

            foreach( $dice as $die )
            {
                if( $previous_type === null )
                    $previous_type = $die['type'];
                else
                {
                    if( $previous_type != $die['type'] )
                        return; // We cannot autorecruit
                }
            }

            // We can autorecruit!
            foreach( $dice as $die )
            {
                $this->doRecruit( $player_id, $die['id'] );

                $credit --;
                if( $credit == 0 )
                    return ;
            }

        }
    }

    function tryAutoSkipManage( $player_id )
    {
        // Auto-skip manage phase if player has no decisions to make
        // Returns true if player was skipped, false if player has decisions to make

        // 1. Can they still recruit?
        $current_credit = self::getUniqueValueFromDB( "SELECT player_credit FROM player WHERE player_id='$player_id'" );
        $citizenry_count = $this->dice->countCardInLocation( 'citizenry', $player_id );

        if( $current_credit > 0 && $citizenry_count > 0 )
            return false; // Still has recruiting decisions

        // 2. Do they have dice they could recall from their construction zones?
        $recallable_dice = $this->dice->countCardInLocation( 'worldconstruct', $player_id )
                         + $this->dice->countCardInLocation( 'devconstruct', $player_id );

        // 3. Do they have dice on their tableau worlds?
        $resource_dice = $this->dice->getCardsInLocation( 'resource' );
        foreach( $resource_dice as $die )
        {
            $world = $this->tiles->getCard( $die['location_arg'] );
            if( $world['location'] == 'tableau' && $world['location_arg'] == $player_id )
                $recallable_dice++;
        }

        if( $recallable_dice > 0 )
            return false; // Has dice they could recall

        // If we auto skip management, we need to also reset credit to 1 if needed (same as manageDone)
        if( $current_credit == 0 )
        {
            self::DbQuery( "UPDATE player SET player_credit = 1 WHERE player_id='$player_id'" );
            self::notifyAllPlayers( "updateCredit", '', array(
                'player_id' => $player_id,
                'credit' => 1,
            ) );
        }

        // Auto-skip this player
        $this->gamestate->setPlayerNonMultiactive( $player_id, "no_more_actions" );
        return true;
    }

    function doRecruit( $player_id, $die_id )
    {
        // Is this die on citizenry
        $die = $this->dice->getCard( $die_id );

        if( $die['location'] != 'citizenry' || $die['location_arg'] != $player_id )
            throw new SystemException( "This die is not in your citizenry" );

        // Place die on cup
        $this->dice->moveCard( $die_id, 'cup', $player_id );

        $current_credit = self::getUniqueValueFromDB( "SELECT player_credit FROM player WHERE player_id='$player_id'" );

        if( $current_credit == 0 )
            throw new UserException( self::_("You have no more credit to do this.") );

        self::DbQuery( "UPDATE player SET player_credit = player_credit-1 WHERE player_id='$player_id'" );
        //self::DbQuery( "UPDATE dice SET card_type_arg='99' WHERE card_id='$die_id'" );  // To mark that it has been recruited this turn

        self::notifyAllPlayers( "updateCredit", '', array(
            'player_id' => $player_id,
            'credit' => $current_credit-1
        ) );

        self::notifyAllPlayers( 'recruitDie', '', array(
            'die' => $die,
            'player_id' => $player_id
        ) );

        self::incStat( 1, 'dice_recruited', $player_id );
    }

    function recruit( $die_id )
    {
        self::checkAction( 'recruit' );

        $player_id = self::getCurrentPlayerId();

        $this->doRecruit( $player_id, $die_id );


        // Try to autorecruit the rest of the die
        self::autorecruit( $player_id );
    }

    function manageDone()
    {
        self::checkAction( 'recruit' );

        $player_id = self::getCurrentPlayerId();

        $current_credit = self::getUniqueValueFromDB( "SELECT player_credit FROM player WHERE player_id='$player_id'" );

        if( $current_credit > 0 && $this->dice->countCardInLocation( 'citizenry', $player_id ) > 0 )
            throw new UserException( self::_("You must recruit dice from your Citizenry until your credits run out or your Citizenry is empty.") );

        if( $current_credit == 0 )
        {
            // No more credits
            self::DbQuery( "UPDATE player SET player_credit = 1 WHERE player_id='$player_id'" );

            self::notifyAllPlayers( "updateCredit", '', array(
                'player_id' => $player_id,
                'credit' => 1,
            ) );
        }

        // If no dice in cup, must recall at least 1 die
        if( $this->dice->countCardInLocation( 'cup', $player_id ) == 0 )
            throw new UserException( self::_("You must RECALL at least one dice from anywhere, otherwise your cup will be empty!") );

        $this->gamestate->setPlayerNonMultiactive( $player_id, "no_more_actions" );
    }

    function exploreDone()
    {
        self::checkAction( 'exploreDone' );

        $player_id = self::getCurrentPlayerId();

        // Check if player still has dice to use
        $dice = $this->dice->getCardsInLocation( 'phase1', $player_id );
        if( count( $dice ) > 0 )
            throw new UserException( self::_("You still have dice to use during this phase."), true );

        // Check if player still has scouted tiles to place
        if( $this->tiles->countCardInLocation( 'scout', $player_id ) > 0 )
            throw new UserException( self::_("You must place your scouted tiles first."), true );

        $this->gamestate->setPlayerNonMultiactive( $player_id, "no_more_actions" );
    }

    function wantToTrade( $die_id, $bNotify=true, $prioritydie=null )
    {
        self::checkAction( 'ship' );

        $player_id = self::getCurrentPlayerId();

        // Must compute how much we can get from this die as a resource, and return it.

        $die = $this->dice->getCard( $die_id );

        if( $die['location'] != 'resource' )
            throw new SystemException( "This die is not a resource" );

        $world = $this->tiles->getCard( $die['location_arg'] );
        $world_type = $this->tiles_types[ $world['type'] ];

//        self::consumeDie( $player_id, 5 );

        $result = array(
            'die' => $die,
            'world' => $world['id'],
            'world_type' => $world['type']
        );

        $result['trade'] = array( 'c' => $world_type['type']+2, 'vp' => 0 );   // 3 for novelty, 4 for rare, ...
        $result['consume'] = array( 'c' => 0, 'vp' => 1 );

        $tiles = self::getTilesWithEffects( array( 'consume_bonus', 'trade_bonus', 'consume_bonus_vp' ), $player_id );
        foreach( $tiles as $tile )
        {
            if( $tile['effect']['power'] == 'consume_bonus' )
            {
                if( ! isset( $tile['effect']['from'] ) || ( $tile['effect']['from'] == $world_type['type'] ) )
                {
                    $result['consume']['c'] += $tile['effect']['nbr'];
                }

            }
            if( $tile['effect']['power'] == 'trade_bonus' )
            {
                if( ! isset( $tile['effect']['good'] ) || ( $tile['effect']['good'] == $world_type['type'] ) )
                {
                    $result['trade']['c'] += $tile['effect']['bonus'];
                }
            }
            if( $tile['effect']['power'] == 'consume_bonus_vp' )
            {
                if( ! isset( $tile['effect']['from'] ) || ( in_array( $world_type['type'], $tile['effect']['from']  ) ) )
                {
                    $result['consume']['vp'] += $tile['effect']['nbr'];
                }
            }
        }


        if( $this->dice_types[ $die['type'] ]['color'] == $world_type['type'] || $die['type'] == 3 )
            $result['consume']['vp'] += 1;    // +1 if the die and the world are same color (or consumption dice)


        // if there is a shipping die from the world's color, or a consumption die (type=3) associated to this player/this phase, +1
        if( $prioritydie === null )
        {
            $consumption_die = self::getObjectListFromDB( "SELECT card_id FROM dice WHERE card_location='phase5' AND card_location_arg='$player_id' AND card_type IN ('3','".($world_type['type']+3)."')", true );

            if( count( $consumption_die ) > 0 )
            {
                $result['consume']['vp'] += 1;
                $result['use_this_die'] = reset( $consumption_die );
            }
        }
        else
        {
            $die_type = self::getUniqueValueFromDB( "SELECT card_type FROM dice WHERE card_id='$prioritydie'" );
            if( $die_type == 3 || $die_type == ($world_type['type']+3) )
                $result['consume']['vp'] += 1;
        }



        if( $bNotify )
            self::notifyPlayer( $player_id, 'tradeinfos', '', $result );

  //      self::inactivePlayerIfNoMoreDie( $player_id, 5 );


        return $result;
    }

    function ship( $die_id, $action, $bGalacticBankers=false, $prioritydie=false )
    {
        $price = self::wantToTrade( $die_id, false, $prioritydie );
        $player_id = self::getCurrentPlayerId();

        if( isset( $price['use_this_die'] ) )
        {
            // wantToTrade specify that we have to use this specific die to guarantee the consume/trade infos
            $prioritydie = $price['use_this_die'];
        }

        $shipdie = self::consumeDie( $player_id, 5, $prioritydie );

        self::incStat( 1, 'dice_used', $player_id );

        if( $action == 'trade' )
        {
            self::incStat( 1, 'dice_trade', $player_id );

            $gain = $price['trade']['c'];
            self::DbQuery( "UPDATE player SET player_credit = LEAST( 10, player_credit+$gain ) WHERE player_id='$player_id'" );
            $new_credit = self::getUniqueValueFromDB( "SELECT player_credit FROM player WHERE player_id='$player_id'" );

            self::notifyAllPlayers( "updateCredit", clienttranslate('Ship: ${player_name} trades a resource from ${world_name} and gets +${gain}$.'), array(
                'i18n' => array('world_name'),
                'player_id' => $player_id,
                'player_name' => self::getCurrentPlayerName(),
                'credit' => $new_credit,
                'gain' => $gain,
                'world_name' => $this->tiles_types[ $price['world_type'] ]['name']
            ) );

            if( $bGalacticBankers )
            {
                // Check there is galactic bankers
                $tiles = self::getTilesWithEffects( 'trade_may_spend_for_vp', $player_id );
                if( count( $tiles ) == 0 )
                    throw new SystemException( "Could not find galactic bankers" );

                // Spend 1$
                self::DbQuery( "UPDATE player SET player_credit = player_credit-1 WHERE player_id='$player_id'" );
                $new_credit = self::getUniqueValueFromDB( "SELECT player_credit FROM player WHERE player_id='$player_id'" );

                self::notifyAllPlayers( "updateCredit", clienttranslate('Galactic Bankers: ${player_name} spends 1$ for 1 VP.'), array(
                    'player_id' => $player_id,
                    'player_name' => self::getCurrentPlayerName(),
                    'credit' => $new_credit
                ) );

                // +1 VP chip
                $gain = 1;
                self::DbQuery( "UPDATE player SET player_vp_chip=player_vp_chip+$gain WHERE player_id='$player_id'" );
                $this->bga->playerScore->inc($player_id, $gain);

                self::incGameStateValue( 'vp_stock', - $gain );

                $new_score = self::getObjectFromDB( "SELECT player_score, player_vp_chip FROM player WHERE player_id='$player_id'" );

                self::notifyAllPlayers( 'scorevp', '', array(
                    'player_id' => $player_id,
                    'player_name' => self::getCurrentPlayerName(),
                    'score' => $new_score,
                    'gain' => $gain
                ) );

            }

        }
        else if( $action == 'consume' )
        {
            self::incStat( 1, 'dice_consume', $player_id );

            $gain = $price['consume']['vp'];

            self::incGameStateValue( 'vp_stock', - $gain );

            self::DbQuery( "UPDATE player SET player_vp_chip=player_vp_chip+$gain WHERE player_id='$player_id'" );
            $this->bga->playerScore->inc($player_id, $gain);

            $new_score = self::getObjectFromDB( "SELECT player_score, player_vp_chip FROM player WHERE player_id='$player_id'" );

            self::notifyAllPlayers( 'scorevp', clienttranslate( 'Ship: ${player_name} consumes a resource from ${world_name} and gets +${gain} VP.'), array(
                'i18n' => array('world_name'),
                'player_id' => $player_id,
                'player_name' => self::getCurrentPlayerName(),
                'score' => $new_score,
                'gain' => $gain,
                'world_name' => $this->tiles_types[ $price['world_type'] ]['name']
            ) );

            if( $price['consume']['c'] > 0 )
            {
                $gain = $price['consume']['c'];
                self::DbQuery( "UPDATE player SET player_credit = LEAST( 10, player_credit+$gain ) WHERE player_id='$player_id'" );
                $new_credit = self::getUniqueValueFromDB( "SELECT player_credit FROM player WHERE player_id='$player_id'" );

                self::notifyAllPlayers( "updateCredit", clienttranslate('Ship: ${player_name} gets +${gain}$.'), array(
                    'i18n' => array('world_name'),
                    'player_id' => $player_id,
                    'player_name' => self::getCurrentPlayerName(),
                    'credit' => $new_credit,
                    'gain' => $gain
                ) );

            }
        }

        // Remove resource
        $this->dice->moveCard( $die_id, 'citizenry', $player_id );
        self::notifyAllPlayers( 'dieShipped', '', array(
            'player_id' => $player_id,
            'world_id' => $price['world'],
            'die' => $price['die']
        ) );

        $players_to_resources = self::getPlayersToResourceNumber();
        if( ! isset( $players_to_resources[ $player_id ] ) )
        {
            // No more resource
            $this->gamestate->setPlayerNonMultiactive( $player_id, "no_more_actions" );
        }
        else
        {
            self::inactivePlayerIfNoMoreDie( $player_id, 5 );
        }
    }

    function recall( $die_id )
    {
        self::checkAction( 'recall' );

        $die = $this->dice->getCard( $die_id );
        $player_id = self::getCurrentPlayerId();

        if( $die['location'] == 'citizenry' && $die['location_arg'] == $player_id )
        {
            throw new SystemException( 'Cannot recall a die from citizenry' );
        }
        else if( $die['location'] == 'cup' && $die['location_arg'] == $player_id )
        {
            throw new SystemException( 'Cannot recall a die from cup' );
        }
        else if( $die['location'] == 'worldconstruct' && $die['location_arg'] == $player_id )
        {   // OK
        }
        else if( $die['location'] == 'devconstruct' && $die['location_arg'] == $player_id )
        {   // OK
        }
        else if( $die['location'] == 'resource' )
        {
            $world = $this->tiles->getCard( $die['location_arg'] );

            if( $world['location'] != 'tableau' || $world['location_arg'] != $player_id )
                throw new UserException( self::_("You cannot remove this die") );
        }
        else
            throw new SystemException( "You cannot recall this die" );

        // Ok, remove this die
        $this->dice->moveCard( $die_id, 'cup', $player_id );

        self::incStat( 1, 'dice_recall', $player_id );

        self::notifyAllPlayers( 'recall', clienttranslate('${player_name} recall a ${type} die.'), array(
            'i18n' => array( 'player_name' ),
            'player_name' => self::getActivePlayerName(),
            'player_id' => $player_id,
            'die' => $die,
            'type' => $this->dice_types[ $die['type'] ]['name']
        ) );
    }

    function removedie( $die_id )
    {
        self::checkAction( 'removedie' );

        $die = $this->dice->getCard( $die_id );
        $player_id = self::getActivePlayerId();

        if( $die['location'] == 'citizenry' && $die['location_arg'] == $player_id )
        {
            // Ok
        }
        else if( $die['location'] == 'cup' && $die['location_arg'] == $player_id )
        {
            // OK
        }
        else if( $die['location'] == 'worldconstruct' && $die['location_arg'] == $player_id )
        {   // OK
        }
        else if( $die['location'] == 'devconstruct' && $die['location_arg'] == $player_id )
        {   // OK
        }
        else if( $die['location'] == 'resource' )
        {
            $world = $this->tiles->getCard( $die['location_arg'] );

            if( $world['location'] != 'tableau' || $world['location_arg'] != $player_id )
                throw new UserException( self::_("You cannot remove this die") );
        }
        else if( substr( $die['location'], 0, 5 ) == 'phase' )
        {
            // Phase die => ok
        }
        else
            throw new UserException( self::_("You cannot remove this die") );

        // Ok, remove this die
        $this->dice->moveCard( $die_id, 'trash' );

        self::notifyAllPlayers( 'removedie', clienttranslate('${player_name} removes a ${type} die.'), array(
            'i18n' => array( 'player_name' ),
            'player_name' => self::getActivePlayerName(),
            'player_id' => $player_id,
            'die' => $die,
            'type' => $this->dice_types[ $die['type'] ]['name']
        ) );

        // Then, get back to the point we were before
        $this->gamestate->nextState( 'endEffect' );
    }

    /*

    Example:

    function playCard( $card_id )
    {
        // Check that this is the player's turn and that it is a "possible action" at this game state (see states.inc.php)
        self::checkAction( 'playCard' );

        $player_id = self::getActivePlayerId();

        // Add your game logic to play a card there
        ...

        // Notify all players about the card played
        self::notifyAllPlayers( "cardPlayed", clienttranslate( '${player_name} plays ${card_name}' ), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'card_name' => $card_name,
            'card_id' => $card_id
        ) );

    }

    */


//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    /*

    Example for game state "MyGameState":

    function argMyGameState()
    {
        // Get some values from the current game situation in database...

        // return values:
        return array(
            'variable1' => $value1,
            'variable2' => $value2,
            ...
        );
    }
    */

    function getAvailableAssigned( $player_id=null )
    {
        $result = array();

        // Get all "asigned" available powers
        $assign = self::getTilesWithEffects( 'reassign', $player_id );

        foreach( $assign as $tile )
        {
            if( $tile['type_arg'] == 10 )
            {
                // Already used!
            }
            else
            {
                // Okay, available!
                $result[] = array(
                    'pid' => $tile['location_arg'],
                    'id' => $tile['id']
                );
            }
        }

        return $result;
    }

    function argAssign()
    {
        $assignpowers = self::getAvailableAssigned();

        return array(
            'dictate' => self::getCollectionFromDB( "SELECT player_id, player_dictate FROM player", true ),
            'assign' => $assignpowers
        );
    }

    function argCurrentEffect()
    {
        $tile_id = self::getGameStateValue( 'current_effect_card' );
        $tile = $this->tiles->getCard( $tile_id );

        return array(
            'i18n' => array( 'card_name' ),
            'card_name' => $this->tiles_types[ $tile['type'] ]['name']
        );
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    /*
        Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
        The action method of state X is called everytime the current game state is set to X.
    */

    function stStartingWorldCombination()
    {
        $this->gamestate->setAllPlayersMultiactive();
    }


    function stRoll()
    {
        self::setGameStateValue( 'selectedphases', 0 );
        self::notifyAllPlayers( 'phasesToActive', '', array( 'phases' => array() ) );

        // Roll all dice in cups, behind screens
        self::notifyAllPlayers( 'simpleNote', clienttranslate( 'All dice are rolled' ), array( ) );

        self::incStat( 1, 'turns_number' );

        $dice = $this->dice->getCardsInLocation( 'cup' );
        $player_to_dice_values = array();

        foreach( $dice as $die )
        {
            $value = bga_rand( 1,6 );
            $player_id = $die['location_arg'];

            if( ! isset( $player_to_dice_values[ $player_id ] ) )
                $player_to_dice_values[ $player_id ] = array();

            $player_to_dice_values[ $player_id ][ $die['id'] ] = $value;

            self::DbQuery( "UPDATE dice SET card_type_arg='$value' WHERE card_id='".$die['id']."'" );
            self::incStat( 1, 'dice_rolled', $player_id );
        }

        self::DbQuery( "UPDATE tile SET card_type_arg='0' WHERE 1" );

        self::autoAssignDice();

        foreach( $player_to_dice_values as $player_id => $dice )
        {
            $dice = array_merge( $this->dice->getCardsInLocation( 'phase1', $player_id ), $this->dice->getCardsInLocation( 'phase2', $player_id ), $this->dice->getCardsInLocation( 'phase3', $player_id ), $this->dice->getCardsInLocation( 'phase4', $player_id ), $this->dice->getCardsInLocation( 'phase5', $player_id ), $this->dice->getCardsInLocation( 'phase6', $player_id ) );

            self::notifyPlayer( $player_id, 'dicerolled_nowait', '', array(
                'dice' => $dice
            ) );

            self::giveExtraTime( $player_id );
        }

        self::DbQuery( "UPDATE player SET player_choosed_phase=NULL, player_dictate='0' WHERE 1" );

        $this->gamestate->nextState('newTurn');
        $this->gamestate->setAllPlayersMultiactive();
    }

    // Take dice in cup and auto-assign them to their correct places on phase table
    function autoAssignDice( $only_player_id = null )
    {
        $dices = $this->dice->getCardsInLocation( 'cup', $only_player_id );

        if( $only_player_id !== null )
        {
            self::DbQuery( "UPDATE player SET player_choosed_phase=NULL WHERE player_id='$only_player_id'" );
        }
        else
        {
            self::DbQuery( "UPDATE player SET player_choosed_phase=NULL WHERE 1" );
        }

        foreach( $dices as $die )
        {
            $player_id = $die['location_arg'];

            $die_type = $die['type'];
            $value = $die['type_arg'];

            $face = $this->dice_types[ $die_type ]['faces'][ $value-1 ];

            if( $face >= 1 && $face <= 5 )
            {
                // This is a simple "phase" thing
                // => affect dice to this phase
                $this->dice->moveCard( $die['id'], 'phase'.$face, $player_id );
            }
            else if( $face == 6 )
            {
                // Wildcard: affect it to phase 1 (player should replace them)
                $this->dice->moveCard( $die['id'], 'phase1', $player_id );
            }
        }

        self::resetMadScientists();
    }

    function resetMadScientists()
    {
        // Check if there is a usable "Mad Scientist" (type = 24)
        // (work only if player has the most blue)
        $player_with_mad_scientist = self::getUniqueValueFromDB( "SELECT card_location_arg FROM tile WHERE card_type='24' AND card_location='tableau' " );

        if( $player_with_mad_scientist !== null )
        {
            // If most novelty world
            $player_to_novelty = array();

            $players = self::loadPlayersBasicInfos();
            foreach( $players as $player_id => $player )
            {
                $player_to_novelty[ $player_id ] = 0;
            }

            $tableautiles = $this->tiles->getCardsInLocation( 'tableau' );

            foreach( $tableautiles as $tableautile )
            {
                if( $this->tiles_types[ $tableautile[ 'type' ] ]['category'] == 'world' && $this->tiles_types[ $tableautile[ 'type' ] ]['type']==1 )
                {
                    $player_to_novelty[ $tableautile['location_arg'] ]++;
                }
            }

            if( getKeyWithMaximum( $player_to_novelty ) == $player_with_mad_scientist )
            {
                // Ok, can use if with maximum level
            }
            else if( in_array( $player_with_mad_scientist, getKeysWithMaximum( $player_to_novelty ) ) )
            {
                // Can be half used
                self::DbQuery( "UPDATE tile SET card_type_arg='1' WHERE card_type='24' AND card_location='tableau'" );
            }
            else
            {
                // Cannot be used
                self::DbQuery( "UPDATE tile SET card_type_arg='10' WHERE card_type='24' AND card_location='tableau'" );
            }
        }
    }

    function stReveal()
    {
        $dice = array_merge( $this->dice->getCardsInLocation( 'phase1' ), $this->dice->getCardsInLocation( 'phase2' ), $this->dice->getCardsInLocation( 'phase3' ), $this->dice->getCardsInLocation( 'phase4' ), $this->dice->getCardsInLocation( 'phase5' ), $this->dice->getCardsInLocation( 'phase6' ), $this->dice->getCardsInLocation( 'cup' ) );

        self::notifyAllPlayers( 'dicerolled', clienttranslate('Dice are revealed'), array(
            'dice' => $dice,
            'players' => self::getCollectionFromDB( "SELECT player_id, player_choosed_phase FROM player" )
        ) );

        // Set activated phases
        $phase_list = self::getObjectListFromDB( "SELECT card_location
            FROM `player`
            INNER JOIN dice ON card_id=player_choosed_phase
            WHERE 1", true );

        $phases_to_active = array();
        if( count( $phase_list ) == 2 )
        {
            // 2 players => must roll a home dice in addition
            $home_dice = bga_rand(1,6);

            if( $home_dice == 6 )
                $home_dice = 1; // There are 2 "Explorer" face on the home dice

            $phases_to_active[ $home_dice ] = true;

            self::notifyAllPlayers( 'simpleNote', clienttranslate('(2 players game) An additional Home die is rolled: ${phase} will be activated!'), array(
                'i18n' => array( 'phase' ),
                'phase' => $this->dice_faces[ $home_dice ]
            ) );
        }

        foreach( $phase_list as $phase )
        {
            $phase = substr( $phase, 5 );
            $phases_to_active[ $phase ] = true;
        }

        self::setGameStateValue( 'selectedphases', self::phase_to_active_to_number( $phases_to_active ) );

        self::notifyAllPlayers( 'phasesToActive', '', array( 'phases' => $phases_to_active ) );

        self::removeDiceFromUnusedPhases();

        $this->gamestate->nextState('reveal');
    }

    function removeDiceFromUnusedPhases()
    {
        $phases_to_active = self::number_to_phases_to_active( self::getGameStateValue( 'selectedphases' ) );

        for( $phase_id = 1; $phase_id<=5; $phase_id ++ )
        {
            if( ! isset( $phases_to_active[ $phase_id ] ) )
            {
                self::returnUnusedDiceToCup( $phase_id );
            }
        }
    }

    function phase_to_active_to_number( $phases_to_active )
    {
        $result = 0;
        if( isset( $phases_to_active[ 1 ] ) )
            $result ++;
        if( isset( $phases_to_active[ 2 ] ) )
            $result += 2;
        if( isset( $phases_to_active[ 3 ] ) )
            $result += 4;
        if( isset( $phases_to_active[ 4 ] ) )
            $result += 8;
        if( isset( $phases_to_active[ 5 ] ) )
            $result += 16;

        return $result;
    }
    function number_to_phases_to_active( $phases_to_active_number )
    {
        $result = array();
        if( $phases_to_active_number & 1 )
            $result[1] = true;
        if( $phases_to_active_number & 2 )
            $result[2] = true;
        if( $phases_to_active_number & 4 )
            $result[3] = true;
        if( $phases_to_active_number & 8 )
            $result[4] = true;
        if( $phases_to_active_number & 16 )
            $result[5] = true;

        return $result;
    }

    // Get player=>dice_nbr for given phase (or null if phase is not selected)
    function getDiceForPhase( $phase_id )
    {
        $phases_to_active = self::number_to_phases_to_active( self::getGameStateValue( 'selectedphases' ) );

        if( ! isset( $phases_to_active[ $phase_id ] ) )
            return null;    // This phase should NOT be activated

        $dice = $this->dice->getCardsInLocation( 'phase'.$phase_id );
        $player_to_dice_nbr = array();

        foreach( $dice as $die )
        {
            if( ! isset( $player_to_dice_nbr[ $die['location_arg'] ] ) )
                $player_to_dice_nbr[ $die['location_arg'] ] = 0;

            $player_to_dice_nbr[ $die['location_arg'] ]++;
        }

        return $player_to_dice_nbr;
    }

    function addTmpDiceForPhase( $phase_id, $player_to_dice_nbr )
    {
        $tiles = self::getTilesWithEffects( 'tmp_die' );

        $bAtLeastOneAdd = false;

        foreach( $tiles as $tile )
        {
            if( $tile['effect']['phase'] == $phase_id )
            {
                $player_id = $tile['location_arg'];

                // Add temporary dice
                $bAtLeastOneAdd = true;

                $tmp_dice = array();
                foreach( $tile['effect']['type'] as $dietype )
                {
                    $tmp_dice[] = array(
                        'type' => $dietype,
                        'type_arg' => 7,   // = tmp dice
                        'nbr' => 1
                    );
                }
                $this->dice->createCards( $tmp_dice, 'phase'.$phase_id, $player_id );
            }
        }

        if( $bAtLeastOneAdd )
        {
            // Signal tmp die
            $sql = "SELECT card_id id, card_type type, card_type_arg type_arg, card_location location, card_location_arg location_arg
                    FROM dice
                    WHERE card_location='phase$phase_id'
                      AND card_type_arg='7'";
            $tmp_dice = self::getObjectListFromDB( $sql );

            self::notifyAllPlayers( 'tmpdie', '', array(
                'dice' => $tmp_dice
            ) );

            $player_to_dice_nbr = self::getDiceForPhase( $phase_id );
        }

        return $player_to_dice_nbr;
    }

    function stStartExplore()
    {
        $player_to_dice_nbr = self::getDiceForPhase( 1 );

        if( $player_to_dice_nbr === null )
        {
            // Nobody selected Explore - skip the phase entirely
            // (Advanced Logistics can only be used DURING the Explore phase, not trigger it)
            $this->returnUnusedDiceToCup( 1 );
            $this->gamestate->nextState('skipPhase');
        }
        else
        {
            // Explore phase is happening - activate players with dice

            self::setGameStateValue( 'saved_dice_nbr', 0 ); // Used on this phase for Alien reseach team

            $player_to_dice_nbr = self::addTmpDiceForPhase( 1, $player_to_dice_nbr );

            // Also activate players with Advanced Logistics (explore_reassign) even if they have no dice
            // They can use Advanced Logistics during the Explore phase
            $advanced_logistics_tiles = self::getTilesWithEffects( 'explore_reassign' );
            $active_players = array_keys( $player_to_dice_nbr );
            foreach( $advanced_logistics_tiles as $tile )
            {
                $player_id = $tile['location_arg'];
                if( ! in_array( $player_id, $active_players ) )
                    $active_players[] = $player_id;
            }

            $this->gamestate->setPlayersMultiactive( $active_players, 'skipPhase', true );

            foreach( $active_players as $player_id )
            {
                self::giveExtraTime( $player_id );
            }

            if( count( $active_players ) > 0 )
                $this->gamestate->nextState('startPhase');
        }
    }

    function stEndExploreAlien()
    {
        $this->returnUnusedDiceToCup( 1 );
        $this->tiles->moveAllCardsInLocation( 'usedexplore', 'deck' );
        $this->tiles->shuffle( 'deck' );
        $this->gamestate->nextState('endPhase');
    }

    function stEndExplore()
    {
        // Is there alien reserch team?
        $tiles = self::getTilesWithEffects( 'explorekeep' );

        if( count( $tiles ) == 1 )
        {
            $tile = reset( $tiles );
            $player_id = $tile['location_arg'];

            $nbr = 1;
            $scouted = $this->tiles->pickCardsForLocation( $nbr, 'deck', 'scout', $player_id );

            if( count( $scouted ) < $nbr )
            {
                // There is not enough in the bag!
                self::notifyAllPlayers( 'simpleNote', clienttranslate('There is not enough tiles in the bag: we are putting back into bag tiles under Explore phase tile.'), array() );

                $this->tiles->moveAllCardsInLocation( 'usedexplore', 'deck' );
                $this->tiles->shuffle( 'deck' );

                $more_scouted = $this->tiles->pickCardsForLocation( $nbr - count( $scouted ), 'deck', 'scout', $player_id );

                foreach( $more_scouted as $tile )
                {
                    $scouted[] = $tile;
                }

                if( count( $scouted ) < $nbr )
                {
                    self::notifyAllPlayers( 'simpleNote', "WARNING: still not enough tiles in the bag :(", array() );
                }
            }

            if( count( $scouted ) > 0 )
            {
                self::notifyAllPlayers( 'simpleNote', clienttranslate('Alien Research Team: ${player_name} picks ${nbr} tiles.'), array('player_name'=> self::getCurrentPlayerName(), 'nbr' => count( $scouted ) ) );
                self::notifyPlayer( $player_id, 'scouted', '', array(
                    'tiles' => self::toDoubleSidedTiles( $scouted )
                ) );


                $this->gamestate->changeActivePlayer( $player_id );
                $this->gamestate->nextState( 'alien_research' );

                self::giveExtraTime( $player_id );

                return  ;
            } else {
                self::notifyAllPlayers( 'simpleNote', "WARNING: skipping Alien Research Team :(", array() );
            }
        }


        $this->returnUnusedDiceToCup( 1 );
        $this->tiles->moveAllCardsInLocation( 'usedexplore', 'deck' );
        $this->tiles->shuffle( 'deck' );

        self::getCreditOnPhase( 1 );
        $this->gamestate->nextState('endPhase');
    }

    function stStartDevelop()
    {
        self::stDevelopOrSettle( 2 );
    }

    function stStartSettle()
    {
        self::stDevelopOrSettle( 3 );
    }

    function stEndDevelop()
    {
        self::applyCreditForGood( 2 );

        self::getCreditOnPhase( 2 );
        $this->gamestate->nextState('endPhase');

    }
    function stEndSettle()
    {
        self::getCreditOnPhase( 3 );
        $this->gamestate->nextState('endPhase');
    }

    function getCostFor( $phase_id, $player_id, $card_to_build_type_id )
    {
        $discounts = self::getTilesWithEffects( ($phase_id==3) ? 'settle_discount' : 'dev_discount', $player_id );
        $discount_total = 0;
        $minimum_after_discount = 0;

        foreach( $discounts as $discount )
        {
            if( $discount['effect']['power'] == 'dev_discount' )
            {
                $minimum_after_discount = 1;

                if( isset( $discount['effect']['option'] ) )
                {
                    // Only dev with reassign power
                    $bWithReassign = false;
                    foreach( $this->tiles_types[ $card_to_build_type_id ]['powers'] as $thiscardpower )
                    {
                        if( in_array( $thiscardpower['power'], array( 'reassign_if_most','reassign', 'three_on_dictate', 'dictate' ) ) )
                            $bWithReassign = true;
                    }

                    if( $bWithReassign )
                        $discount_total ++;
                }
                else
                    $discount_total ++;
            }
            else
            {
                if( isset( $discount['effect']['option'] ) )
                {
                    if( $discount['effect']['option'] == 'only_for_gray_doubled' )
                    {
                        if( $this->tiles_types[ $card_to_build_type_id ]['type'] == 0 )
                        {
                            $discount_total += 2;
                            if( $minimum_after_discount == 0 )
                                $minimum_after_discount = 2;
                        }
                    }
                    else if( $discount['effect']['option'] == 'only_for_green_yellow' )
                    {
                        if( $this->tiles_types[ $card_to_build_type_id ]['type'] == 3 ||  $this->tiles_types[ $card_to_build_type_id ]['type'] == 4 )
                        {
                            $discount_total ++;
                        }
                    }
                }
                else
                {
                    $minimum_after_discount = 1;
                    $discount_total ++;
                }
            }
        }

        $cost = max( $minimum_after_discount, $this->tiles_types[ $card_to_build_type_id ]['cost'] - $discount_total );
        // Free Trade Zone does not increase cost from 1 to 2 (fix bug #8774)
        $cost = min( $cost, $this->tiles_types[ $card_to_build_type_id ]['cost'] );

        return $cost;
    }

    function stDevelopOrSettle( $phase_id, $only_this_player=null, $start_with_die=null )
    {
        $players = self::loadPlayersBasicInfos();

        $construction_zone = ( $phase_id == 3 ) ? 'worldconstruct' : 'devconstruct';
        $construction_zone_tile = ( $phase_id == 3 ) ? 'bw' : 'bd';

        $players_to_active = array();

        $player_to_dice_nbr = self::getDiceForPhase( $phase_id );

        if( $player_to_dice_nbr !== null )
        {
            // As long as these phase occurs, we MUST check all players (ex: there may be dev/worlds ready to be built with previous dice)
            foreach( $players as $player_id => $dummy )
            {
                if( ! isset( $player_to_dice_nbr[ $player_id ]  ) )
                    $player_to_dice_nbr[ $player_id ] = 0;
            }
        }

        $card_effect = self::getGameStateValue( 'current_effect_card' );
        $bMakeSurePhaseIsActive = false;
        $force_build_card_id = null;
        if( $card_effect != 0 )
        {
            if( self::getGameStateValue( 'current_effect_beforebuild' ) == 0 )
            {
                // We must finish this card effect before anything else
                $card_to_build = $this->tiles->getCard( $card_effect );
                self::applyEffect( $card_to_build['location_arg'], $card_to_build['type'], $card_to_build['id'], null, true );

                self::setGameStateValue( 'current_effect_card', 0 );
                $bMakeSurePhaseIsActive = true;
            }
            else
            {
                // We just finished an effect for a card BEFORE it has been built, so we must build it now!
                $force_build_card_id = self::getGameStateValue( 'current_effect_card' );
                $card_to_build = $this->tiles->getCard( $card_effect );
                $player_id = substr( $card_to_build['location'], 2 );
                self::setGameStateValue( 'current_effect_card', 0 );

                if( $player_to_dice_nbr === null )
                    $player_to_dice_nbr = array();

                if( ! isset( $player_to_dice_nbr[ $player_id ] ) )
                {
                    $player_to_dice_nbr[ $player_id ] = 0;
                }

                // Then, in any case, we must START with this player. So if this player is not the first item of the array, we must make sure it is now
                reset( $player_to_dice_nbr );
                if( key( $player_to_dice_nbr ) != $player_id )
                {
                    $value = $player_to_dice_nbr[ $player_id ];
                    unset( $player_to_dice_nbr[ $player_id ] );

                    $array_to_append = array();
                    $array_to_append[ $player_id ] = $value;
                    $player_to_dice_nbr = $array_to_append + $player_to_dice_nbr;
                }
            }
        }

        $last_die_used = null;

        if( $player_to_dice_nbr === null && $bMakeSurePhaseIsActive == false )
        {
            $this->returnUnusedDiceToCup( $phase_id );
            $this->gamestate->nextState('skipPhase');
        }
        else
        {
            // Active all players who have dice for this phase
            if( $bMakeSurePhaseIsActive && $player_to_dice_nbr === null )
                $player_to_dice_nbr = array();


            foreach( $player_to_dice_nbr as $player_id => $dice_nbr )
            {

                if( $only_this_player === null || $player_id == $only_this_player )
                {
                    // Get dice already on building zone
                    $already_there = $this->dice->countCardInLocation( $construction_zone, $player_id );
                    $card_to_build = $this->tiles->getCardOnTop( $construction_zone_tile.$player_id );

                    if( $card_to_build !== null )
                    {
                        $cost = self::getCostFor( $phase_id, $player_id, $card_to_build['type'] );

                        $remaining_cost = $cost - $already_there;
                    }
                    else
                    {
                        // No card to build : all dice from this phase must go to cup now
                        $dice = $this->dice->getCardsInLocation( 'phase'.$phase_id, $player_id );
                        $this->dice->moveAllCardsInLocation( 'phase'.$phase_id, 'cup', $player_id, $player_id );

                        foreach( $dice as $die )
                        {
                            self::notifyAllPlayers( 'returnedDie', '', array(
                                'player_id' => $player_id,
                                'die' => $die
                            ) );

                            self::incStat( 1, 'dice_returned', $player_id );
                        }

                        $cost = 999;
                        $remaining_cost = 999;
                        $dice_nbr = 0;
                    }

                    $dice = $this->dice->getCardsInLocation( 'phase'.$phase_id, $player_id );


                    // At this step, we must determine if we must use the dice of players automatically or manually.
                    // We are using dice automatically if and only if:
                    // _ there are exactly or not enough dice to complete the next construction (=> all dice will be moved anyway)
                    // _ if not, if all remaining dice are the same
                    // (So that we can do it manually if there are MORE dice than the cost, and if there is a real choice to be made)

                    $bSwitchToManualMode = false;

                    if( $start_with_die == null && $force_build_card_id === null )
                    {   // we did NOT specify any die to start with

                        if( count( $dice ) <= $remaining_cost )
                        {
                            // We can proceed automatically as all dice will be moved to the construction anyway
                        }
                        else
                        {
                            // Are all remaining dice the same?
                            $bAllTheSame = true;
                            $previous_type = null;
                            foreach( $dice as $die )
                            {
                                if( $previous_type === null )
                                    $previous_type = $die['type'];
                                else
                                {
                                    if( $previous_type != $die['type'] )
                                        $bAllTheSame = false;
                                }
                            }

                            if( $bAllTheSame == false )
                                $bSwitchToManualMode = true;
                        }
                    }
                    else
                    {
                        // We specified a die to used! So let's use it first!
                        // OR we have a "force_build_card_id" so we must jump to card construction now
                    }


                    if( $bSwitchToManualMode )
                    {
                        $players_to_active[]=$player_id;
                    }
                    else
                    {
                        while( $dice_nbr >= 0 )
                        {
                            if( $remaining_cost <= 0 || ( $force_build_card_id !== null && $force_build_card_id == $card_to_build['id'] )  )
                            {
                                // This card has been built!
                                $tableautiles = self::getTilesWithEffects( ($phase_id==3) ?  'back_dice_on_settle' : 'back_dice_on_dev', $player_id );

                                $build_option = '';

                                foreach( $tableautiles as $tableautile )
                                {
                                    if( $tableautile['effect']['power'] == 'back_dice_on_dev' && $force_build_card_id === null )
                                    {
                                        // This player must choose 2 die to recruit for free, among developers
                                        // => Treat this an interrupting effect (before doing anything with the card

                                        // We must PAUSE everything and jump into our new state
                                        self::setGameStateValue( 'current_effect_phase', $phase_id );
                                        self::setGameStateValue( 'current_effect_card', $card_to_build['id'] );
                                        self::setGameStateValue( 'current_effect_beforebuild', 1 );
                                        self::setGameStateValue( 'saved_dice_nbr', 0 );
                                        $this->gamestate->changeActivePlayer( $player_id );
                                        $this->gamestate->nextState( 'back_dice_on_dev' );
                                        return 'back_dice';
                                    }

                                    if( $tableautile['effect']['power'] == 'back_dice_on_settle' && $force_build_card_id === null )
                                    {
                                        if( $tableautile['effect']['nbr'] == 'all' )
                                        {
                                            // Save all military dice from construction
                                            $build_option = 'military_die_saved';
                                        }
                                        else
                                        {
                                            // This player must choose 2 die to recruit for free, among developers
                                            // => Treat this an interrupting effect (before doing anything with the card

                                            // We must PAUSE everything and jump into our new state
                                            self::setGameStateValue( 'current_effect_phase', $phase_id );
                                            self::setGameStateValue( 'current_effect_card', $card_to_build['id'] );
                                            self::setGameStateValue( 'current_effect_beforebuild', 1 );
                                            self::setGameStateValue( 'saved_dice_nbr', 0 );
                                            $this->gamestate->changeActivePlayer( $player_id );
                                            $this->gamestate->nextState( 'back_dice_on_settle' );
                                            return 'back_dice';
                                        }
                                    }
                                    else if( $tableautile['effect']['power'] == 'back_dice_on_settle' && $tableautile['effect']['nbr'] == 'all' )
                                    {
                                        // Galactic Exchange + New Military Order combo FIX
                                        $build_option = 'military_die_saved';
                                    }


                                }

                                if( $force_build_card_id !== null )
                                {
                                    $cost -= self::getGameStateValue( 'saved_dice_nbr' );
                                    $force_build_card_id = null;
                                }

                                if( $this->build_tile( $phase_id, $card_to_build, $cost, $player_id, $last_die_used, $build_option ) )
                                    return 'effect_with_interact';     // Note: effect with some interaction
                                else
                                {
                                    // Get the next card to build
                                    $card_to_build = $this->tiles->getCardOnTop( $construction_zone_tile.$player_id );

                                    if( $card_to_build === null )
                                    {
                                        // No card to build : all dice from this phase must go to cup now
                                        $dice = $this->dice->getCardsInLocation( 'phase'.$phase_id, $player_id );
                                        $this->dice->moveAllCardsInLocation( 'phase'.$phase_id, 'cup', $player_id, $player_id );

                                        foreach( $dice as $die )
                                        {
                                            self::notifyAllPlayers( 'returnedDie', '', array(
                                                'player_id' => $player_id,
                                                'die' => $die
                                            ) );

                                            self::incStat( 1, 'dice_returned', $player_id );
                                        }
                                        $dice = array();
                                        $dice_nbr = 0;

                                        $cost = 999;
                                        $remaining_cost = 999;
                                    }
                                    else
                                    {
                                        // Reset the local variables with the new card to built
                                        $cost = self::getCostFor( $phase_id, $player_id, $card_to_build['type'] );
                                        $already_there = $this->dice->countCardInLocation( $construction_zone, $player_id );
                                        $remaining_cost = $cost - $already_there;
                                    }
                                }

                            }
                            else if( $dice_nbr > 0 )
                            {
                                // We are placing dice here while there are dice to place here
                                // + one last time to check if the item has been built

                                if( $start_with_die === null )
                                {
                                    $die = array_pop( $dice );
                                }
                                else
                                {
                                    $die = null;
                                    foreach( $dice as $thisdie )
                                    {
                                        if( $thisdie['id'] == $start_with_die )
                                            $die = $thisdie;
                                    }

                                    if( $die === null )
                                        throw new SystemException("Could not found die for construction!");
                                }

                                // Place this die on construction zone
                                $this->dice->moveCard( $die['id'], $construction_zone, $player_id );

                                if( $phase_id == 2 )
                                    self::incStat( 1, 'dice_dev', $player_id );
                                else
                                    self::incStat( 1, 'dice_settle', $player_id );
                                self::incStat( 1, 'dice_used', $player_id );


                                $last_die_used = $die;

                                // Decrease cost
                                $remaining_cost --;

                                // Notify
                                self::notifyAllPlayers( "dice_to_construction", '', array(
                                    'die' => $die,
                                    'player_id' => $player_id,
                                    'zone' =>  ( $phase_id == 3 ) ? 'world' : 'dev'
                                ) );

                                $dice_nbr --;

                                if( $start_with_die !== null )
                                {
                                    // We must placed this die only for now, so stop here !
                                    $dice_nbr = 0;
                                }
                            }
                            else if( $dice_nbr <= 0 )
                                $dice_nbr --;
                        }
                    }
                }
            }

            if( $start_with_die !== null )
                return 'done';  // Work has been done with this die, no effect has been triggered, chooseDiceForConstr must continue the process

            if( $only_this_player )
            {
                if( count( $players_to_active ) > 0 )
                    return 'manual';    // Still something to do for this player
                else
                {
                    // Nothing more to do => inactivate this player
                    $this->gamestate->setPlayerNonMultiactive( $only_this_player, 'endPhase' );
                    return 'done';
                }
            }
            else
            {
                $this->gamestate->setPlayersMultiactive( $players_to_active, 'endPhase', true );

                if( count( $players_to_active ) > 0 )
                    return 'manual';    // Still something to do for at least 1 player
                else
                    return 'done';
            }
        }
    }


    function chooseDiceForConstr( $die_id )
    {
        self::checkAction( 'chooseDiceForConstr' );

        $player_id = self::getCurrentPlayerId();

        $die = $this->dice->getCard( $die_id );
        $state = $this->gamestate->getCurrentMainState()->toArray();

        if( $state['name'] == 'startDevelop' )
        {
            if( $die['location'] != 'phase2' || $die['location_arg'] != $player_id )
            {
                    throw new UserException( self::_("You must choose a die from your Develop phase column.") );
            }
        }
        else
        {
            if( $die['location'] != 'phase3' || $die['location_arg'] != $player_id )
            {
                throw new UserException( self::_("You must choose a die from your Settle phase column.") );
            }
        }

        if( $state['name'] == 'startDevelop' )
        {
            $phase_id = 2;
        }
        else
        {
            $phase_id = 3;
        }

        // At first, place the die we choosed
        $after_first_place = $this->stDevelopOrSettle( $phase_id, $player_id, $die['id'] );

        if( $after_first_place != 'done' )
            return; // Player has some action to do

        // Then, try to auto-assign the rest of the dice
        $this->stDevelopOrSettle( $phase_id, $player_id );
    }

    function donotuse()
    {
        self::checkAction( 'donotuse' );
        $this->gamestate->nextState( 'donotuse' );
    }

    function build_tile( $phase_id, $card_to_build, $cost, $player_id, $last_die_used, $build_option )
    {
        $construction_zone = ( $phase_id == 3 ) ? 'worldconstruct' : 'devconstruct';
        $construction_zone_tile = ( $phase_id == 3 ) ? 'bw' : 'bd';
        $players = self::loadPlayersBasicInfos();

        // => move it to tableau
        $this->tiles->moveCard( $card_to_build['id'], 'tableau', $player_id );

        self::notifyAllPlayers( 'pre_card_built', '', array() );


        // Move dice (x cost) to citizenry
        $dice_on_construction = $this->dice->getCardsInLocation( $construction_zone, $player_id );
        $dice_to_remove = array();
        for( $i=0;$i<$cost;$i++ )
        {
            $die = array_pop( $dice_on_construction );

            if( $build_option == 'military_die_saved' && $die['type'] == 2 )
            {
                // Specific (new galactic order)
                $this->dice->moveCard( $die['id'], 'cup', $player_id );

                self::notifyAllPlayers( "savedie", '', array(
                    'die' => $die,
                    'player_id' => $player_id,
                    'zone' =>  'world'
                ) );
            }
            else
            {
                // Normal situation
                $this->dice->moveCard( $die['id'], 'citizenry', $player_id );

                $dice_to_remove[] = $die;
            }
        }

        // Score
        $gain = $this->tiles_types[ $card_to_build['type'] ]['cost'];
        $this->bga->playerScore->inc($player_id, $gain);

        $new_score = self::getObjectFromDB( "SELECT player_score, player_vp_chip FROM player WHERE player_id='$player_id'" );


        self::notifyAllPlayers( 'card_built', clienttranslate('${player_name} builds ${card_name} (cost: ${realcost}).'), array(
            'i18n' => array( 'card_name' ),
            'player_name' => $players[ $player_id ]['player_name'],
            'player_id' => $player_id,
            'card_name' => $this->tiles_types[ $card_to_build['type'] ]['name'],
            'cost' => $cost,
            'realcost' => $cost + self::getGameStateValue('saved_dice_nbr'),
            'card' => $card_to_build,
            'dice' => $dice_to_remove,
            'tableaucount' => $this->tiles->countCardInLocation( 'tableau', $player_id ),
            'zone' =>  ( $phase_id == 3 ) ? 'world' : 'dev',
            'score' => $new_score,
            'tile_back' => $this->tiles->getCardOnTop( $construction_zone_tile.$player_id )
        ) );

        self::setGameStateValue( 'saved_dice_nbr', 0 );

        // Effects "when built"
        $when_built_tiles = self::getTilesWithEffects( 'credit_when_build', $player_id );
        foreach( $when_built_tiles as $tile )
        {
            if( $tile['id'] != $card_to_build['id'] )   // Not the card we just built
            {
                $bValid = false;

                $gain = 1;

                if( isset( $tile['effect']['option'] ) && $tile['effect']['option'] == 'dev_only' )
                {
                    if( $phase_id == 2 )
                        $bValid = true;
                }
                else if( isset( $tile['effect']['option'] ) && $tile['effect']['option'] == 'world_only_plus_brown' )
                {
                    if( $phase_id == 3 )
                    {
                        $bValid = true;

                        if( $this->tiles_types[ $card_to_build['type'] ]['type']==2 )
                            $gain = 2;
                    }
                }
                else if( ! isset( $tile['effect']['option'] ) )
                    $bValid = true;

                if( $bValid )
                {
                    // Okay for credit!
                    self::DbQuery( "UPDATE player SET player_credit = LEAST( 10, player_credit+$gain ) WHERE player_id='$player_id'" );
                    $new_credit = self::getUniqueValueFromDB( "SELECT player_credit FROM player WHERE player_id='$player_id'" );

                    self::notifyAllPlayers( "updateCredit", clienttranslate('${card_name}: ${player_name} gets +${nbr}$.'), array(
                        'i18n' => array( 'card_name' ),
                        'card_name' => $this->tiles_types[ $tile['type'] ]['name'],
                        'player_id' => $player_id,
                        'player_name' => $players[ $player_id ]['player_name'],
                        'nbr' => $gain,
                        'credit' => $new_credit
                    ) );

                }
            }
        }

        $next_state = self::applyEffect( $player_id, $card_to_build['type'], $card_to_build['id'], $last_die_used );

        if( $next_state == '' )
        {
        }
        else
        {
            // We must PAUSE everything and jump into our new state
            self::setGameStateValue( 'current_effect_phase', $phase_id );
            self::setGameStateValue( 'current_effect_card', $card_to_build['id'] );
            self::setGameStateValue( 'current_effect_beforebuild', 0 );
            $this->gamestate->changeActivePlayer( $player_id );
            $this->gamestate->nextState( $next_state );
            return true;
        }

        return false;

    }

    function stEndEffect()
    {
        if( self::getGameStateValue( 'current_effect_phase' ) == 2 )
            $this->gamestate->nextState( 'develop' );
        else if( self::getGameStateValue( 'current_effect_phase' ) == 3 )
            $this->gamestate->nextState( 'settle' );
    }

    function applyEffect( $player_id, $tile_type_id, $tile_id, $last_die_used = null, $bSkipInteractives=false, $bInitialEffect=false )
    {
        $tile_type = $this->tiles_types[ $tile_type_id ];
        $players = self::loadPlayersBasicInfos();

        foreach( $tile_type['powers'] as $power )
        {
            if( $power['power'] == 'gaindie' )
            {   // Gain a new die

                $nbr = 1;
                if( isset( $power['nbr'] ) )
                    $nbr = $power['nbr'];

                $die_type = $power['type'];

                $target = 'citizenry';
                if( isset( $power['target'] ) )
                    $target = $power['target'];

                for( $i=0;$i<$nbr;$i++ )
                {
                    // Take the die a place it on target
                    $die = $this->dice->pickCardForLocation( 'deck'.$die_type, $target, $player_id );

                    self::notifyAllPlayers( "newdie", clienttranslate('${card_name}: ${player_name} takes a ${die_name} die.'), array(
                        'i18n' => array( 'card_name', 'die_name' ),
                        'card_name' => $tile_type['name'],
                        'player_name' => $players[ $player_id ]['player_name'],
                        'player_id' => $player_id,
                        'die_name' => $this->dice_types[ $die_type ]['name'],
                        'die' => $die,
                        'target' => $target,
                        'tile_id' => $tile_id
                    ) );
                }

                self::incStat( $nbr, 'dice_number', $player_id );
            }
            else if( $power['power'] == 'gaingood' )
            {
                // Take a new die and place it on this world
                $world_type = $tile_type['type'];
                $dice_type = $world_type + 3;

                // Move this die to this world
                $die = $this->dice->pickCardForLocation( 'deck'.$dice_type, 'resource', $tile_id );

                self::notifyAllPlayers( 'produce', clienttranslate('${player_name} produces a resource on ${card_name}.'), array(
                    'i18n' => array( 'card_name' ),
                    'player_name' => $players[ $player_id ]['player_name'],
                    'player_id' => $player_id,
                    'card_name' => $tile_type['name'],
                    'die' => $die,
                    'card_id' => $tile_id,
                    'from_stock' => true
                ) );

                self::incStat( 1, 'dice_number', $player_id );
            }
            else if( $power['power'] == 'removedie' )
            {
                self::incStat( -1, 'dice_number', $player_id );

                // Must choose a die to remove
                if( ! $bSkipInteractives )
                    return "removedie";
            }
            else if( $power['power'] == 'credit' )
            {
                if( isset( $power['phase'] ) )
                {   // Reccurent effect on each phase,but not here
                }
                else
                {
                    $gain = $power['nbr'];
                    self::DbQuery( "UPDATE player SET player_credit = LEAST( 10, player_credit+$gain ) WHERE player_id='$player_id'" );
                    $new_credit = self::getUniqueValueFromDB( "SELECT player_credit FROM player WHERE player_id='$player_id'" );

                    self::notifyAllPlayers( "updateCredit", clienttranslate('${card_name}: ${player_name} gets +${nbr}$.'), array(
                        'i18n' => array( 'card_name' ),
                        'card_name' => $tile_type['name'],
                        'player_id' => $player_id,
                        'player_name' => $players[ $player_id ]['player_name'],
                        'nbr' => $gain,
                        'credit' => $new_credit
                    ) );
                }
            }
            else if( $power['power'] == 'credit_on_gamestart' && $bInitialEffect )
            {
                self::DbQuery( "UPDATE player SET player_credit = 8 WHERE player_id='$player_id'" );

                self::notifyAllPlayers( "updateCredit", '', array(
                    'player_id' => $player_id,
                    'credit' => 8
                ) );

            }

        }

        return '';

    }

    function getProductionPossibilities()
    {
        $player_to_production = array();

        // Get all dice from this phase
        $dice = $this->dice->getCardsInLocation( 'phase4' );


        // Get existing resources
        $resources = $this->dice->getCardsInLocation( 'resource' );
        $worlds_with_resources = array();
        foreach( $resources as $resource )
        {
            if( ! isset( $worlds_with_resources[ $resource['location_arg'] ] ) )
                $worlds_with_resources[ $resource['location_arg'] ] = 1;
            else
                $worlds_with_resources[ $resource['location_arg'] ] ++ ;
        }

        // Get all cards in tableau
        $tableau = $this->tiles->getCardsInLocation( 'tableau' );


        $player_to_goodlimit = array();
        foreach( $tableau as $tile )
        {
            if( ! isset( $player_to_goodlimit[$tile['location_arg']] ) )
                $player_to_goodlimit[$tile['location_arg']] = 1;

            if( $tile['type'] == 115 ) // Galactic reserve
            {
                $player_to_goodlimit[$tile['location_arg']] = 2;
            }
        }


/*        // Step 1: //////////////////////
        //
        // For each die, try to find a production world WITH THE SAME COLOR, no resource on it, from the same player
        //

        foreach( $dice as $die )
        {
            $player_id = $die['location_arg'];
            $bDiePlaced = false;

            foreach( $tableau as $tile )
            {
                if( $tile['location_arg'] == $player_id )
                {
                    $tile_type = $this->tiles_types[ $tile['type'] ];

                    if( $tile_type['category'] == 'world'  && $tile_type['type'] != 0 )
                    {
                        if( $tile_type['type'] == $this->dice_types[ $die['type'] ]['color'] ) // Same color?
                        {
                            if( ! isset( $worlds_with_resources[ $tile['id'] ] ) )
                            {
                                // Move this die to this world
                                $this->dice->moveCard( $die['id'], 'resource', $tile['id'] );
                                $worlds_with_resources[ $tile['id'] ] = true;
                                $bDiePlaced = true;

                                self::notifyAllPlayers( 'produce', clienttranslate('${player_name} produces a resource on ${card_name}.'), array(
                                    'i18n' => array( 'card_name' ),
                                    'player_name' => $players[ $player_id ]['player_name'],
                                    'player_id' => $player_id,
                                    'card_name' => $tile_type['name'],
                                    'die' => $die,
                                    'card_id' => $tile['id']
                                ) );
                            }
                        }
                    }
                }

                if( $bDiePlaced )
                    break ;

            }
        }

        // Step 2: //////////////////////
        //
        // For each die, try to find a production world, no resource on it, from the same player
        //
*/

        foreach( $dice as $die )
        {
            $player_id = $die['location_arg'];

            foreach( $tableau as $tile )
            {
                if( $tile['location_arg'] == $player_id )
                {
                    $tile_type = $this->tiles_types[ $tile['type'] ];

                    if( $tile_type['category'] == 'world' && $tile_type['type'] != 0 )
                    {
                        if( ! isset( $worlds_with_resources[ $tile['id'] ] ) || ( $worlds_with_resources[ $tile['id'] ] < $player_to_goodlimit[ $tile['location_arg'] ] ) )
                        {
                            // This is possible to produce here
                            $player_to_production[ $player_id ] = true;
                        }
                    }
                }
            }
        }

        return $player_to_production;
    }

    function stStartProduce()
    {
        $players = self::loadPlayersBasicInfos();
        $player_to_dice_nbr = self::getDiceForPhase( 4 );

        if( $player_to_dice_nbr === null )
        {
            $this->returnUnusedDiceToCup( 4 );

            // Do not apply any effect as the phase is skipped
            //self::endProducePhaseEffects();

            $this->gamestate->nextState('skipPhase');
        }
        else
        {
            $player_to_production = self::getProductionPossibilities();

            if( count( $player_to_production ) == 0 )
            {
                $this->returnUnusedDiceToCup( 4 );
                self::endProducePhaseEffects();
                $this->gamestate->nextState( 'skipPhase' );
            }
            else
            {
                foreach( $player_to_production as $player_id => $player )
                {
                    self::giveExtraTime( $player_id );
                }

                $this->gamestate->setPlayersMultiactive( array_keys( $player_to_production ), 'skipPhase', true );

                $this->gamestate->nextState( 'startPhase' );
            }
        }
    }

    function produce( $tile_id, $prioritydie )
    {
        self::checkAction( 'produce' );

        $tile = $this->tiles->getCard( $tile_id );
        $tile_type = $this->tiles_types[ $tile['type'] ];
        $player_id = self::getCurrentPlayerId();

        if( $tile['location'] != 'tableau' ||$tile['location_arg']!= $player_id)
            throw new SystemException( "This tile is not in your tableau" );

        $already_there = $this->dice->getCardsInLocation( 'resource', $tile_id );

        if( count( $already_there ) > 0 )
        {
            $limit = 1;

            $extragoodtiles = self::getTilesWithEffects( 'extragood', $player_id );
            if( count( $extragoodtiles ) > 0 )
                $limit = 2;

            if( count( $already_there ) >= $limit )
                throw new UserException( self::_("There is already a resource on this world") );
        }

        if( $tile_type['category'] != 'world' )
            throw new UserException( self::_("You must choose a world.") );

        if( $tile_type['type'] == 0 )
            throw new UserException( self::_("This type of world (gray) cannot produce any resource.") );

        $dice = $this->dice->getCardsInLocation( 'phase4', $player_id );

        $selected_die = null;

        if( $prioritydie !== null && $prioritydie != 0 )
        {
            // Find this dice in priority
            foreach( $dice as $die )
            {
                if( $die['id'] == $prioritydie )
                    $selected_die = $die;
            }
        }

        // Otherwise, find a dice from the same color
        if( $selected_die === null )
        {
            foreach( $dice as $die )
            {
                if( $tile_type['type'] == $this->dice_types[ $die['type'] ]['color'] )
                    $selected_die = $die;
            }
        }

        // Finally, take the first one
        if( $selected_die === null )
            $selected_die = reset( $dice );

        // Produce !
        $this->dice->moveCard( $selected_die['id'], 'resource', $tile_id );

        self::incStat( 1, 'dice_produce', $player_id );
        self::incStat( 1, 'dice_used', $player_id );


        self::notifyAllPlayers( 'produce', clienttranslate('${player_name} produces a resource on ${card_name}.'), array(
            'i18n' => array( 'card_name' ),
            'player_name' => self::getCurrentPlayerName(),
            'player_id' => $player_id,
            'card_name' => $tile_type['name'],
            'die' => $selected_die,
            'card_id' => $tile['id']
        ) );

        $player_to_production = self::getProductionPossibilities();


        if( ! isset( $player_to_production[ $player_id ] ) )
        {
            $this->gamestate->setPlayerNonMultiactive( $player_id, 'no_more_actions' );
        }
    }

    function stEndProduce()
    {
        $this->returnUnusedDiceToCup( 4 );
        self::endProducePhaseEffects();
        $this->gamestate->nextState( 'endPhase' );
    }

    function applyCreditForGood( $phase_id )
    {
        $tiles = self::getTilesWithEffects( array( 'credit_for_good', 'credit_for_die' ) );
        $players = self::loadPlayersBasicInfos();

        foreach( $tiles as $tile )
        {
            if( $tile['effect']['phase'] == $phase_id )
            {
                $player_id = $tile['location_arg'];

                if( $tile['effect']['power'] == 'credit_for_good' )
                {

                    if( isset( $tile['effect']['dice'] ) )
                    {
                        $bDieCase = true;
                        $die_type = $tile['effect']['dice'];
                    }
                    else
                    {
                        $bDieCase = false;
                        $good_type = $tile['effect']['good'];
                    }

                    $count_goods = 0;

                    if( ! $bDieCase )
                    {
                        // Get number of goods from this type on this player tableau

                        $tile_type_to_ress = self::getCollectionFromDB( "SELECT tile.card_type, COUNT( dice.card_id )
                                                       FROM dice
                                                       INNER JOIN tile ON tile.card_id=dice.card_location_arg
                                                       WHERE dice.card_location='resource'
                                                       AND tile.card_location_arg='$player_id'
                                                       GROUP BY tile.card_id", true );

                        foreach( $tile_type_to_ress as $tile_type_id => $ress_nbr )
                        {
                            if( $this->tiles_types[ $tile_type_id ]['type'] == $good_type )
                            {
                                $count_goods += $ress_nbr;
                            }
                        }

                    }
                    else
                    {
                        // Only dice with the correct color


                        $count_goods = 2 * self::getUniqueValueFromDB( "SELECT COUNT( dice.card_id )
                                                       FROM dice
                                                       INNER JOIN tile ON tile.card_id=dice.card_location_arg
                                                       WHERE dice.card_location='resource'
                                                       AND tile.card_location_arg='$player_id'
                                                       AND dice.card_type='$die_type'", true );

                    }


                    if( $count_goods > 0 )
                    {
                        self::DbQuery( "UPDATE player SET player_credit = LEAST( 10, player_credit+$count_goods ) WHERE player_id='$player_id'" );
                        $new_credit = self::getUniqueValueFromDB( "SELECT player_credit FROM player WHERE player_id='$player_id'" );

                        self::notifyAllPlayers( "updateCredit", clienttranslate('${card_name}: ${player_name} gets +${nbr}$.'), array(
                            'i18n' => array( 'card_name' ),
                            'card_name' => $this->tiles_types[ $tile['type'] ]['name'],
                            'player_id' => $player_id,
                            'player_name' => $players[ $player_id ]['player_name'],
                            'nbr' => $count_goods,
                            'credit' => $new_credit
                        ) );
                    }
                }
                else if( $tile['effect']['power'] == 'credit_for_die' )
                {
                    // Count novelty die on citizenry
                    $count = self::getUniqueValueFromDB( "SELECT COUNT( card_id ) FROM dice WHERE card_location='citizenry' AND card_location_arg='$player_id' AND card_type='4'" );

                    if( $count > 0 )
                    {
                        self::DbQuery( "UPDATE player SET player_credit = LEAST( 10, player_credit+$count ) WHERE player_id='$player_id'" );
                        $new_credit = self::getUniqueValueFromDB( "SELECT player_credit FROM player WHERE player_id='$player_id'" );

                        self::notifyAllPlayers( "updateCredit", clienttranslate('${card_name}: ${player_name} gets +${nbr}$.'), array(
                            'i18n' => array( 'card_name' ),
                            'card_name' => $this->tiles_types[ $tile['type'] ]['name'],
                            'player_id' => $player_id,
                            'player_name' => $players[ $player_id ]['player_name'],
                            'nbr' => $count,
                            'credit' => $new_credit
                        ) );

                    }
                }

            }
        }

    }

    // Effects that should be triggered at the end of "produce" phase
    function endProducePhaseEffects()
    {
        self::applyCreditForGood( 4 );
        self::getCreditOnPhase( 4 );

    }

    // Trigger "get credit on phase X" effects
    function getCreditOnPhase( $phase_id )
    {
        $tiles = self::getTilesWithEffects( 'credit' );
        $players = self::loadPlayersBasicInfos();

        foreach( $tiles as $tile )
        {
            if( isset( $tile['effect']['phase'] ) && $tile['effect']['phase'] == $phase_id )
            {
                // Get credit

                $player_id = $tile['location_arg'];
                $gain = $tile['effect']['nbr'];
                self::DbQuery( "UPDATE player SET player_credit = LEAST( 10, player_credit+$gain ) WHERE player_id='$player_id'" );
                $new_credit = self::getUniqueValueFromDB( "SELECT player_credit FROM player WHERE player_id='$player_id'" );

                self::notifyAllPlayers( "updateCredit", clienttranslate('${card_name}: ${player_name} gets +${nbr}$.'), array(
                    'i18n' => array( 'card_name' ),
                    'card_name' => $this->tiles_types[ $tile['type'] ]['name'],
                    'player_id' => $player_id,
                    'player_name' => $players[ $player_id ]['player_name'],
                    'nbr' => $gain,
                    'credit' => $new_credit
                ) );

            }
        }

        if( $phase_id == 1 || $phase_id == 4 )
        {
            $tiles = self::getTilesWithEffects( array( 'credit_if_most' ) );

            foreach( $tiles as $tile )
            {
                $player_id = $tile['location_arg'];

                // If most development
                $player_to_devcount = array();
                $tableautiles = $this->tiles->getCardsInLocation( 'tableau' );

                foreach( $tableautiles as $tableautile )
                {
                    if( $this->tiles_types[ $tableautile[ 'type' ] ]['category'] == 'dev' )
                    {
                        if( ! isset( $player_to_devcount[ $tableautile['location_arg'] ] ) )
                            $player_to_devcount[ $tableautile['location_arg'] ] = 0;
                        $player_to_devcount[ $tableautile['location_arg'] ]++;
                    }
                }

                if( getKeyWithMaximum( $player_to_devcount ) == $player_id )
                {
                    $gain = 1;

                    self::DbQuery( "UPDATE player SET player_credit = LEAST( 10, player_credit+$gain ) WHERE player_id='$player_id'" );
                    $new_credit = self::getUniqueValueFromDB( "SELECT player_credit FROM player WHERE player_id='$player_id'" );


                    self::notifyAllPlayers( "updateCredit", clienttranslate('${card_name}: ${player_name} gets +${nbr}$.'), array(
                        'i18n' => array( 'card_name' ),
                        'card_name' => $this->tiles_types[ $tile['type'] ]['name'],
                        'player_id' => $player_id,
                        'player_name' => $players[ $player_id ]['player_name'],
                        'nbr' => $gain,
                        'credit' => $new_credit
                    ) );

                }
            }
        }

        if( $phase_id == 5 )
        {
            $tiles = self::getTilesWithEffects( array( 'ship_bonus_per_twomilitary', 'vp_on_phase', 'credit_if_high_cost' ) );

            foreach( $tiles as $tile )
            {
                $player_id = $tile['location_arg'];

                if( $tile['effect']['power'] == 'ship_bonus_per_twomilitary' )
                {
                    $military_dice_in_citizenry = count( $this->dice->getCardsOfTypeInLocation( 2, null, 'citizenry', $player_id ) );
                    $gain = ceil( $military_dice_in_citizenry / 2 );

                    self::DbQuery( "UPDATE player SET player_credit = LEAST( 10, player_credit+$gain ) WHERE player_id='$player_id'" );
                    $new_credit = self::getUniqueValueFromDB( "SELECT player_credit FROM player WHERE player_id='$player_id'" );

                    self::notifyAllPlayers( "updateCredit", clienttranslate('${card_name}: ${player_name} gets +${nbr}$.'), array(
                        'i18n' => array( 'card_name' ),
                        'card_name' => $this->tiles_types[ $tile['type'] ]['name'],
                        'player_id' => $player_id,
                        'player_name' => $players[ $player_id ]['player_name'],
                        'nbr' => $gain,
                        'credit' => $new_credit
                    ) );
                }
                else if( $tile['effect']['power'] == 'vp_on_phase' )
                {
                    $gain = 1;

                    self::incGameStateValue( 'vp_stock', - $gain );

                    self::DbQuery( "UPDATE player SET player_vp_chip=player_vp_chip+$gain WHERE player_id='$player_id'" );
                    $this->bga->playerScore->inc($player_id, $gain);

                    $new_score = self::getObjectFromDB( "SELECT player_score, player_vp_chip FROM player WHERE player_id='$player_id'" );

                    self::notifyAllPlayers( 'scorevp', clienttranslate( '${card_name}: ${player_name} gets ${gain} VP.'), array(
                        'i18n' => array('card_name'),
                        'card_name' => $this->tiles_types[ $tile['type'] ]['name'],
                        'player_id' => $player_id,
                        'player_name' => $players[ $player_id ]['player_name'],
                        'score' => $new_score,
                        'gain' => $gain
                    ) );

                }
                else if( $tile['effect']['power'] == 'credit_if_high_cost' )
                {
                    $tableautiles = $this->tiles->getCardsInLocation( 'tableau' );



                    $higher_world_cost = -1;
                    $higher_world_players = array();

                    foreach( $tableautiles as $tableautile )
                    {
                        if( $this->tiles_types[ $tableautile[ 'type' ] ]['category'] == 'world' )
                        {
                            if( $this->tiles_types[ $tableautile[ 'type' ] ]['cost'] > $higher_world_cost )
                            {
                                $higher_world_players = array( $tableautile['location_arg'] => true );
                                $higher_world_cost= $this->tiles_types[ $tableautile[ 'type' ] ]['cost'];
                            }
                            else if( $this->tiles_types[ $tableautile[ 'type' ] ]['cost'] == $higher_world_cost )
                            {
                                $higher_world_players[ $tableautile['location_arg'] ] = true;
                            }
                        }
                    }

                    if( $higher_world_cost >= 0 )
                    {
                        if( isset( $higher_world_players[ $player_id ] ) && count( $higher_world_players ) == 1 )   // If player is the only one to have world with this cost
                        {
                            // +1$
                            $gain = 1;

                            self::DbQuery( "UPDATE player SET player_credit = LEAST( 10, player_credit+$gain ) WHERE player_id='$player_id'" );
                            $new_credit = self::getUniqueValueFromDB( "SELECT player_credit FROM player WHERE player_id='$player_id'" );

                            self::notifyAllPlayers( "updateCredit", clienttranslate('${card_name}: ${player_name} gets +${nbr}$.'), array(
                                'i18n' => array( 'card_name' ),
                                'card_name' => $this->tiles_types[ $tile['type'] ]['name'],
                                'player_id' => $player_id,
                                'player_name' => $players[ $player_id ]['player_name'],
                                'nbr' => $gain,
                                'credit' => $new_credit
                            ) );

                        }
                    }
                }

            }
        }

    }

    function getTilesWithEffects( $effect, $player_id=null )
    {
        $result = array();

        if( ! is_array( $effect ) )

            $effect = array( $effect );

        // Get all tiles from all tableau
        $tiles = $this->tiles->getCardsInLocation( 'tableau', $player_id );

        foreach( $tiles as $tile )
        {
            $tile_type = $this->tiles_types[ $tile['type'] ];

            foreach( $tile_type['powers'] as $power )
            {
                if( in_array( $power['power'], $effect ) )
                {
                    // Found a tile with this effect !

                    $tile['effect'] = $power;
                    $result[] = $tile;
                }
            }
        }

        return $result;
    }

    function getPlayersToResourceNumber()
    {
        return self::getCollectionFromDB( "SELECT tile.card_location_arg, COUNT( dice.card_id )
                                           FROM dice
                                           INNER JOIN tile ON tile.card_id=dice.card_location_arg
                                           WHERE dice.card_location='resource'
                                           GROUP BY tile.card_location_arg", true );
    }

    function stStartShip()
    {
        $player_to_dice_nbr = self::getDiceForPhase( 5 );

        if( $player_to_dice_nbr === null )
        {
            $this->returnUnusedDiceToCup( 5 );
            $this->gamestate->nextState('skipPhase');
        }
        else
        {
            // Active all players who have dice for this phase

            $player_to_dice_nbr = self::addTmpDiceForPhase( 5, $player_to_dice_nbr );

            $players_to_resources = self::getPlayersToResourceNumber();

            $player_to_active = array_intersect( array_keys( $player_to_dice_nbr ), array_keys( $players_to_resources ) );

            foreach( $player_to_active as $player_id )
            {
                self::giveExtraTime( $player_id );
            }

            $this->gamestate->setPlayersMultiactive( $player_to_active, 'no_action_on_phase', true );

            if( count( $player_to_active ) > 0 )
                $this->gamestate->nextState('startPhase');
        }

    }

    function stEndShip()
    {
        $this->returnUnusedDiceToCup( 5 );
        self::getCreditOnPhase( 5 );

        $this->gamestate->nextState('endPhase');
    }

    function stStartManage()
    {
        $this->gamestate->setAllPlayersMultiactive();
        $this->gamestate->nextState( 'startPhase' );

        self::notifyAllPlayers( 'pauseBeforeRecruit', '', array() );

//        self::DbQuery( "UPDATE dice SET card_type_arg='0' WHERE 1" );  // To mark that it has been recruited this turn

        $endOfGameReason = self::isEndOfGame();
        $bForceAutorecruit = ( $endOfGameReason !== null );

        $players = self::loadPlayersBasicInfos();
        foreach( $players as $player_id => $dummy )
        {
            $this->autorecruit( $player_id, $bForceAutorecruit );
            if( ! $this->tryAutoSkipManage( $player_id ) )
            {
                self::giveExtraTime( $player_id );
            }
        }
    }

    function finalScoringScore( $player_id, $tile_type, $score )
    {
        $players = self::loadPlayersBasicInfos();

        $this->bga->playerScore->inc($player_id, $score);

        self::notifyAllPlayers( 'score', clienttranslate('${card_name}: ${player_name} scores ${score} VP.'), array(
            'i18n' => array( 'card_name' ),
            'card_name' => $tile_type['name'],
            'score' => $score,
            'player_name' => $players[ $player_id ]['player_name'],
            'player_id' => $player_id
        ) );
    }

    function finalScoring()
    {
        // Score development with points
        $tiles = $this->tiles->getCardsInLocation( 'tableau' );

        $player_to_points = array();

        foreach( $tiles as $tile )
        {
            $player_id = $tile['location_arg'];

            if( ! isset( $player_to_points[ $player_id ] ) )
            {
                $player_to_points[ $player_id ] = array(
                    'cost' => 0,
                    'dev' => 0,
                    'chip' => 0,
                    'tile' => 0
                );
            }

            $player_to_points[ $player_id ]['cost'] += $this->tiles_types[ $tile['type'] ]['cost'];
            $player_to_points[ $player_id ]['tile'] ++;

            $tile_type = $this->tiles_types[ $tile['type'] ];

            if( $tile_type['category'] == 'dev' && $tile_type['cost'] == 6 )
            {

                if( $tile_type['name'] == 'Galactic Federation' )
                {
//                    html += _('Add one-third of your total base development cost (rounded up).');

                    $total_dev = 0;
                    foreach( $tiles as $thistile )
                    {
                        if( $thistile['location_arg'] == $player_id )
                        {
                            if( $this->tiles_types[ $thistile['type'] ]['category'] == 'dev' )
                                $total_dev += $this->tiles_types[ $thistile['type'] ]['cost'];
                        }
                    }

                    $gain = ceil( $total_dev/3 );
                    self::finalScoringScore( $player_id, $tile_type, $gain );
                    $player_to_points[ $player_id ]['dev'] += $gain;
                }
                else if( $tile_type['name'] == 'Galactic Exchange' )
                {
//                    html += _('+1 VP for each different color of dice you own.');

                    $player_dice = array();
                    $dice = $this->dice->getCardsInLocation( array( 'cup','citizenry','devconstruct','worldconstruct','resource' ) );
                    foreach( $dice as $die )
                    {
                        if( $die['location'] != 'resource' && $die['location_arg'] == $player_id )
                            $player_dice[] = $die['type'];
                        else if( $die['location'] == 'resource' )
                        {
                            // Must check if player own this world
                            foreach( $tiles as $thistile )
                            {
                                if( $thistile['id'] == $die['location_arg'] && $thistile['location_arg'] == $player_id )
                                    $player_dice[] = $die['type'];
                            }
                        }
                    }

                    $player_dice = array_unique( $player_dice );

                    $gain = count( $player_dice );
                    self::finalScoringScore( $player_id, $tile_type, $gain );
                    $player_to_points[ $player_id ]['dev'] += $gain;
                }
                else if( $tile_type['name'] == 'New Galactic Order' )
                {
//                    html += _('+2 VP per 3 Military (red) dice you own (rounded up).');

                    $player_dice = array();
                    $dice = $this->dice->getCardsInLocation( array( 'cup','citizenry','devconstruct','worldconstruct','resource' ) );
                    foreach( $dice as $die )
                    {
                        if( $die['location'] != 'resource' && $die['location_arg'] == $player_id && $die['type']==2 )
                            $player_dice[] = $die['type'];
                        else if( $die['location'] == 'resource' )
                        {
                            // Must check if player own this world
                            foreach( $tiles as $thistile )
                            {
                                if( $thistile['id'] == $die['location_arg'] && $thistile['location_arg'] == $player_id && $die['type']==2 )
                                    $player_dice[] = $die['type'];
                            }
                        }
                    }

                    $gain = 2*ceil( count( $player_dice ) / 3 );
                    self::finalScoringScore( $player_id, $tile_type, $gain );
                    $player_to_points[ $player_id ]['dev'] += $gain;

                }
                else if( $tile_type['name'] == 'Mining League' )
                {
//                    html += _('+2 VP per Rare Elements (brown) world in your tableau.');

                    $total_brown = 0;
                    foreach( $tiles as $thistile )
                    {
                        if( $thistile['location_arg'] == $player_id )
                        {
                            if( $this->tiles_types[ $thistile['type'] ]['category'] == 'world' && $this->tiles_types[ $thistile['type'] ]['type'] == 2 )
                                $total_brown ++;
                        }
                    }

                    $gain = 2*$total_brown;
                    self::finalScoringScore( $player_id, $tile_type, $gain );
                    $player_to_points[ $player_id ]['dev'] += $gain;

                }
                else if( $tile_type['name'] == 'Free Trade Association' )
                {
//                    html += _('Add half of your total Novelty world cost (rounded up).');

                    $total_nov = 0;
                    foreach( $tiles as $thistile )
                    {
                        if( $thistile['location_arg'] == $player_id )
                        {
                            if( $this->tiles_types[ $thistile['type'] ]['category'] == 'world' && $this->tiles_types[ $thistile['type'] ]['type'] == 1 )
                                $total_nov += $this->tiles_types[ $thistile['type'] ]['cost'];
                        }
                    }

                    $gain = ceil( $total_nov/2 );
                    self::finalScoringScore( $player_id, $tile_type, $gain );
                    $player_to_points[ $player_id ]['dev'] += $gain;

                }
                else if( $tile_type['name'] == 'New Economy' )
                {
//                    html += _('+1VP per development without a Reassign power (including this one).');

                    $total_dev = 0;
                    foreach( $tiles as $thistile )
                    {
                        if( $thistile['location_arg'] == $player_id )
                        {
                            if( $this->tiles_types[ $thistile['type'] ]['category'] == 'dev' )
                            {
                                $bHasReassign = false;

                                foreach( $this->tiles_types[ $thistile['type'] ]['powers'] as $power )
                                {
                                    if( in_array( $power['power'], array( 'reassign_if_most','reassign', 'three_on_dictate', 'dictate' ) ) )
                                        $bHasReassign = true;
                                }

                                if( !$bHasReassign )
                                    $total_dev ++;
                            }
                        }
                    }

                    $gain = $total_dev;
                    self::finalScoringScore( $player_id, $tile_type, $gain );
                    $player_to_points[ $player_id ]['dev'] += $gain;

                }
                else if( $tile_type['name'] == 'Galactic Renaissance' )
                {
//                    html += _('+1, 2, 3, 4, ... VP for 1, 3, 6, 10, ... VP in chips.');

                    $chips = self::getUniqueValueFromDB( "SELECT player_vp_chip FROM player WHERE player_id='$player_id'" );

                    if( $chips < 1 )
                        $gain = 0;
                    else if( $chips < 3 )
                        $gain = 1;
                    else if( $chips < 6 )
                        $gain = 2;
                    else if( $chips < 10 )
                        $gain = 3;
                    else if( $chips < 15 )
                        $gain = 4;
                    else if( $chips < 21 )
                        $gain = 5;
                    else if( $chips < 28 )
                        $gain = 6;
                    else if( $chips < 36 )
                        $gain = 7;

                    else if( $chips < 45 )
                        $gain = 8;
                    else if( $chips < 55 )
                        $gain = 9;
                    else
                        $gain = 10;

                    self::finalScoringScore( $player_id, $tile_type, $gain );
                    $player_to_points[ $player_id ]['dev'] += $gain;
                }
                else if( $tile_type['name'] == 'Galactic Reserves' )
                {
//                    html += _('+1 VP per good (at the end of the game).');

                    $player_dice = array();
                    $dice = $this->dice->getCardsInLocation( array( 'resource' ) );
                    foreach( $dice as $die )
                    {
                        // Must check if player own this world
                        foreach( $tiles as $thistile )
                        {
                            if( $thistile['id'] == $die['location_arg'] && $thistile['location_arg'] == $player_id )
                                $player_dice[] = $die['type'];
                        }
                    }

                    $gain = count( $player_dice );
                    self::finalScoringScore( $player_id, $tile_type, $gain );
                    $player_to_points[ $player_id ]['dev'] += $gain;

                }
                else if( $tile_type['name'] == 'Galactic Bankers' )
                {
//                    html += _('+1 VP per development in your tableau.');

                    $total_dev = 0;
                    foreach( $tiles as $thistile )
                    {
                        if( $thistile['location_arg'] == $player_id )
                        {
                            if( $this->tiles_types[ $thistile['type'] ]['category'] == 'dev' )
                                $total_dev ++;
                        }
                    }

                    $gain = $total_dev;
                    self::finalScoringScore( $player_id, $tile_type, $gain );
                    $player_to_points[ $player_id ]['dev'] += $gain;

                }
                else if( $tile_type['name'] == 'System Diversification' )
                {
//                    html += _('Add half of your total base Reassign-power development cost (rounded up).');

                    $total_dev = 0;
                    foreach( $tiles as $thistile )
                    {
                        if( $thistile['location_arg'] == $player_id )
                        {
                            if( $this->tiles_types[ $thistile['type'] ]['category'] == 'dev' )
                            {
                                foreach( $this->tiles_types[ $thistile['type'] ]['powers'] as $power )
                                {
                                    if( in_array( $power['power'], array( 'reassign_if_most','reassign', 'three_on_dictate', 'dictate' ) ) )
                                        $total_dev += $this->tiles_types[ $thistile['type'] ]['cost'];
                                }
                            }
                        }
                    }

                    $gain = ceil( $total_dev / 2 );
                    self::finalScoringScore( $player_id, $tile_type, $gain );
                    $player_to_points[ $player_id ]['dev'] += $gain;

                }
            }
        }

        // Compute score_aux (credits + dice in cup)
        $players = self::loadPlayersBasicInfos();
        $dice_cup = $this->dice->countCardsByLocationArgs( 'cup' );
        $credits = self::getCollectionFromDB( "SELECT player_id, player_credit FROM player" );

        foreach( $players as $player_id => $dummy )
        {
            $cup = 0;
            if( isset( $dice_cup[ $player_id ] ) )
                $cup = $dice_cup[ $player_id ];

            $this->bga->playerScoreAux->set(
                $player_id, $credits[$player_id]['player_credit'] + $cup);
        }

        foreach( $player_to_points as $player_id => $score )
        {
            self::setStat( $score['dev'], 'points_dev', $player_id );
            self::setStat( $score['cost'], 'points_tiles', $player_id );
            self::setStat( $score['tile'], 'tile_count', $player_id );
        }

        $player_to_chips = self::getCollectionFromDB( "SELECT player_id, player_vp_chip FROM player", true );
        foreach( $player_to_chips as $player_id => $chips )
        {
            self::setStat( $chips, 'points_chips', $player_id );
        }

    }

    // Return null if not end of game, otherwise return end of game reason
    function isEndOfGame()
    {
        if( self::getGameStateValue( 'vp_stock' ) <= 0 )
        {
            return 'chip';
        }
        else
        {
            $player_to_count = $this->tiles->countCardsByLocationArgs( 'tableau' );
            foreach( $player_to_count as $player_id => $count )
            {
                if( $count >= 12 )
                {
                    return 'tableau';
                }
            }
        }

        return null;
    }

    function stEndTurn()
    {
        $endOfGameReason = self::isEndOfGame();

        if( $endOfGameReason !== null )
        {
            if( $endOfGameReason == 'chip' )
            {
                self::notifyAllPlayers( 'simpleNote', clienttranslate('The victory point chips pool is exhausted: End of the Game!'), array() );
            }
            else
            {
                self::notifyAllPlayers( 'simpleNote', clienttranslate('There is one tableau with 12 tiles or more: End of the Game!'), array() );
            }

            self::finalScoring();
            $this->gamestate->nextState( 'endGame' );
            return;

        }
        else
        {
            $this->gamestate->nextState( 'nextTurn' );
        }
    }

    /*

    Example for game state "MyGameState":

    function stMyGameState()
    {
        // Do some stuff ...

        // (very often) go to another gamestate
        $this->gamestate->nextState( 'some_gamestate_transition' );
    }
    */

//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    /*
        zombieTurn:

        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).
    */

    function zombieTurn( $state, $active_player )
    {
    	$statename = $state['name'];

        if ($state['type'] === "activeplayer") {
            switch ($statename) {
                default:
                    $this->gamestate->nextState( "zombiePass" );
                	break;
            }

            return;
        }

        if ($state['type'] === "multipleactiveplayer") {
            // Make sure player is in a non blocking status for role turn
            $this->gamestate->setPlayerNonMultiactive( $active_player, 'zombiePass' );

            return;
        }

        throw new SystemException( "Zombie mode not supported at this game state: ".$statename );
    }

///////////////////////////////////////////////////////////////////////////////////:
////////// DB upgrade
//////////

    /*
        upgradeTableDb:

        You don't have to care about this until your game has been published on BGA.
        Once your game is on BGA, this method is called everytime the system detects a game running with your old
        Database scheme.
        In this case, if you change your Database scheme, you just have to apply the needed changes in order to
        update the game database and allow the game to continue to run with your new version.

    */

    function upgradeTableDb( $from_version )
    {
        // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345

        // Example:
//        if( $from_version <= 1404301345 )
//        {
//            $sql = "ALTER TABLE xxxxxxx ....";
//            self::DbQuery( $sql );
//        }
//        if( $from_version <= 1405061421 )
//        {
//            $sql = "CREATE TABLE xxxxxxx ....";
//            self::DbQuery( $sql );
//        }
//        // Please add your future database scheme changes here
//
//


    }


///////////////////////////////////////////////////////////////////////////////////:
////////// DEBUG functions
//////////

    // Debug: add this card to player construction zone
    function ac( $card_type_id )
    {
        $player_id = self::getCurrentPlayerId();



        $side = $this->tiles_types[ $card_type_id ]['category'];
        $target_location = ( $side == 'dev' ) ? 'bd'.$player_id :'bw'.$player_id;

        $sql = "SELECT MAX(card_location_arg) FROM tile WHERE card_location='$target_location'";
        $top = self::getUniqueValueFromDB( $sql );
        $above = ( $top == null ) ? 0 : ($top + 2);
        $sql = "INSERT INTO tile (card_type, card_type_arg, card_location, card_location_arg) VALUES
                    ('$card_type_id', '0', '$target_location', '$above')";
        self::DbQuery( $sql );
        $tile_id = self::DbGetLastId();

        self::notifyAllPlayers( 'debug_ac', '', array(
            'tile' => $this->tiles->getCard( $tile_id ),
            'side' => $side,
        ) );
    }

    function debug_ac(int $card_type_id) {
        $this->ac($card_type_id);
    }

    // Debug: add this card to player tableau
    function act( $card_type_id )
    {
        $player_id = self::getCurrentPlayerId();

        $sql = "INSERT INTO tile (card_type, card_type_arg, card_location, card_location_arg) VALUES
                    ('$card_type_id','0','tableau','$player_id')";
        self::DbQuery( $sql );
        $tile_id = self::DbGetLastId();



        self::notifyAllPlayers( 'debug_act', '', array(
            'tile' => $this->tiles->getCard( $tile_id )
        ) );
    }

    function debug_act(int $card_type_id) {
        $this->act($card_type_id);
    }

    function debug_effect(int $card_id )
    {
        $card = $this->tiles->getCard( $card_id );

        self::applyEffect( self::getCurrentPlayerId(), $card['type'], $card_id );
    }
}
