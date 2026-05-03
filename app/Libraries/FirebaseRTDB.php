<?php
namespace App\Libraries;
use Kreait\Firebase\Factory;

class FirebaseRTDB
{
    public function updateRealtimeDatabaseValue($parentReference, $updateValue)
    {
        try {
			$factory            =	(new Factory)->withServiceAccount(FIREBASE_PRIVATE_KEY_PATH)->withDatabaseUri(FIREBASE_RTDB_URI);
            $database           =	$factory->createDatabase();
            $referenceParent    =	$database->getReference(FIREBASE_RTDB_MAINREF_NAME.$parentReference);
            $referenceParentGet =	$referenceParent->getValue();

            if($referenceParentGet !== null && !is_null($referenceParentGet)){
                $referenceParent->set($updateValue);
            }
		} catch (\Throwable $th) {
			return $th->getMessage();
		}
        return true;
    }
}