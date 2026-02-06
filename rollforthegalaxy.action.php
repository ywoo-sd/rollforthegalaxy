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
 * rollforthegalaxy.action.php
 *
 * RollForTheGalaxy main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/rollforthegalaxy/rollforthegalaxy/myAction.html", ...)
 *
 */


  class action_rollforthegalaxy extends APP_GameAction
  {
    // Constructor: please do not modify
   	public function __default()
  	{
  	    if( self::isArg( 'notifwindow') )
  	    {
            $this->view = "common_notifwindow";
  	        $this->viewArgs['table'] = self::getArg( "table", AT_posint, true );
  	    }
  	    else
  	    {
            $this->view = "rollforthegalaxy_rollforthegalaxy";
            self::trace( "Complete reinitialization of board game" );
      }
  	}

  	function chooseComboFlip()
  	{
        self::setAjaxMode();
  	    $this->game->chooseComboFlip();
        self::ajaxResponse( );
  	}

  	function doneChooseCombo()
  	{
        self::setAjaxMode();
  	    $this->game->doneChooseCombo();
        self::ajaxResponse( );
  	}

  	function resetAssign()
  	{
        self::setAjaxMode();
  	    $this->game->resetAssign();
        self::ajaxResponse( );
  	}


  	function doneAssign()
  	{
        self::setAjaxMode();
  	    $this->game->doneAssign();
        self::ajaxResponse( );
  	}

  	function reassign()
  	{
        self::setAjaxMode();

        $die_id = self::getArg( "die", AT_posint, true );
        $phase_id = self::getArg( "phase", AT_posint, true );
        $bActivate = self::getArg( 'activate', AT_bool, true );
        $power_id = self::getArg( 'power', AT_posint, false, null );

  	    $this->game->reassign( $die_id, $phase_id, $bActivate, $power_id );
        self::ajaxResponse( );
  	}

  	function dictate()
  	{
        self::setAjaxMode();

        $die_id = self::getArg( "die", AT_posint, true );

  	    $this->game->dictate( $die_id );
        self::ajaxResponse( );
  	}


  	function stock()
  	{
        self::setAjaxMode();
        $prioritydie = self::getArg( "prioritydie", AT_posint, false, null );
  	    $this->game->stock( $prioritydie );
        self::ajaxResponse( );
  	}

  	function scout()
  	{
        self::setAjaxMode();
        $prioritydie = self::getArg( "prioritydie", AT_posint, false, null );
  	    $this->game->scout( $prioritydie );
        self::ajaxResponse( );
  	}

  	function pickScoutedTile()
  	{
        self::setAjaxMode();
        $tile_id = self::getArg( "tile", AT_posint, true );
        $side = self::getArg( "side", AT_alphanum, true );
        $bOnTop = self::getArg( "top", AT_bool, false, false );
  	    $this->game->pickScoutedTile( $tile_id, $side, $bOnTop );
        self::ajaxResponse( );
  	}

    function recruit()
    {
        self::setAjaxMode();
        $die_id = self::getArg( "die", AT_posint, true );
  	    $this->game->recruit( $die_id );
        self::ajaxResponse( );
    }

    function manageDone()
    {
        self::setAjaxMode();
  	    $this->game->manageDone( );
        self::ajaxResponse( );
    }

    function resetRecruit()
    {
        self::setAjaxMode();
        $this->game->resetRecruit();
        self::ajaxResponse();
    }

    function exploreDone()
    {
        self::setAjaxMode();
  	    $this->game->exploreDone( );
        self::ajaxResponse( );
    }

    function wantToTrade()
    {
        self::setAjaxMode();
        $die_id = self::getArg( "die", AT_posint, true );
        $prioritydie = self::getArg( "prioritydie", AT_posint, false, null );
  	    $this->game->wantToTrade( $die_id, true, $prioritydie );
        self::ajaxResponse( );

    }

    function trade()
    {
        self::setAjaxMode();
        $die_id = self::getArg( "die", AT_posint, true );
        $bGalacticBankers = self::getArg( 'gb', AT_bool, true );
        $prioritydie = self::getArg( "prioritydie", AT_posint, false, null );
  	    $this->game->ship( $die_id, 'trade', $bGalacticBankers , $prioritydie );
        self::ajaxResponse( );
    }
    function consume()
    {
        self::setAjaxMode();
        $die_id = self::getArg( "die", AT_posint, true );
        $prioritydie = self::getArg( "prioritydie", AT_posint, false, null );
  	    $this->game->ship( $die_id, 'consume', false, $prioritydie );
        self::ajaxResponse( );
    }

    function scoutdiscard()
    {
        self::setAjaxMode();

        $cards_raw = self::getArg( "cards", AT_numberlist, true );

        // Removing last ';' if exists
        if( substr( $cards_raw, -1 ) == ';' )
            $cards_raw = substr( $cards_raw, 0, -1 );
        if( $cards_raw == '' )
            $cards = array();
        else
            $cards = explode( ';', $cards_raw );


  	    $this->game->scoutdiscard( $cards );

        self::ajaxResponse( );

    }

    function produce()
    {
        self::setAjaxMode();
        $tile_id = self::getArg( "tile", AT_posint, true );
        $prioritydie = self::getArg( "prioritydie", AT_posint, false, null );
  	    $this->game->produce( $tile_id, $prioritydie );
        self::ajaxResponse( );
    }

    function donotuse()
    {
        self::setAjaxMode();
  	    $this->game->donotuse( );
        self::ajaxResponse( );
    }


    function savedie()
    {
        self::setAjaxMode();
        $die_id = self::getArg( "die", AT_posint, true );
  	    $this->game->savedie( $die_id, 'dev' );
        self::ajaxResponse( );
    }
    function savediew()
    {
        self::setAjaxMode();
        $die_id = self::getArg( "die", AT_posint, true );
  	    $this->game->savedie( $die_id, 'world' );
        self::ajaxResponse( );
    }

  	function removedie()
  	{
        self::setAjaxMode();
        $die_id = self::getArg( "die", AT_posint, true );
  	    $this->game->removedie( $die_id );
        self::ajaxResponse( );
  	}

  	function chooseDiceForConstr()
  	{
        self::setAjaxMode();
        $die_id = self::getArg( "die", AT_posint, true );
  	    $this->game->chooseDiceForConstr( $die_id );
        self::ajaxResponse( );
  	}

  	function recall()
  	{
        self::setAjaxMode();
        $die_id = self::getArg( "die", AT_posint, true );
  	    $this->game->recall( $die_id );
        self::ajaxResponse( );
  	}

    function advancedlogistics()
    {
        self::setAjaxMode();
        $tile_id = self::getArg( "tile", AT_posint, true );
        $action = self::getArg( "al", AT_alphanum, true );
  	    $this->game->advancedlogistics( $tile_id, $action );
        self::ajaxResponse( );
    }

    /*

    Example:

    public function myAction()
    {
        self::setAjaxMode();

        // Retrieve arguments
        // Note: these arguments correspond to what has been sent through the javascript "ajaxcall" method
        $arg1 = self::getArg( "myArgument1", AT_posint, true );
        $arg2 = self::getArg( "myArgument2", AT_posint, true );

        // Then, call the appropriate method in your game logic, like "playCard" or "myAction"
        $this->game->myAction( $arg1, $arg2 );

        self::ajaxResponse( );
    }

    */

  }
