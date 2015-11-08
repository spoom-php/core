<?php namespace Framework;

use Framework\Helper\Feasible;
use Framework\Helper\FeasibleInterface;

class Test implements FeasibleInterface {
  use Feasible {
    execute as executeFeasible;
  }

  public function execute( $name, $argument = null ) {
    return $this->executeFeasible( $name, $argument );
  }

  protected function frameworkTestSimple( EventData $event, StorageInterface $data ) {
    $event->set( 'output', [ $event->get( EventData::NAMESPACE_ARGUMENT . ':test' ), $data->get( 'test' ) ] );
    
    $event->stopped = true;
    $event->prevented = true;
  }

  protected function frameworkTestAdvance( EventData $event, StorageInterface $data ) {
    $event->set( 'output', [ $event->get( EventData::NAMESPACE_ARGUMENT . ':test' ), $data->get( 'test' ) ] );
  }
}
