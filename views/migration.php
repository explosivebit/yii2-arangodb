<?php
/**
 * This view is used by console/controllers/MigrateController.php
 * The following variables are available in this view:
 */

/* @var $className string the new migration class name */
echo "<?php\n";
?>

class <?= $className ?> extends \devgroup\arangodb\Migration
{
    public function up()
    {
		$this->createCollection('<?= $className ?>',[]);
    }

    public function down()
    {
        $this->dropCollection('<?= $className ?>');
    }
}
