/**
 * InscriptionDetailViewController
 */
sygeforApp.controller('InscriptionDetailViewController', ['$scope', '$state', '$trainingBundle', '$dialog', 'data', 'inscriptionStatusList', 'presenceStatusList', function($scope, $state, $trainingBundle, $dialog, data, inscriptionStatusList, presenceStatusList) {
    $scope.inscription = data.inscription;
    $scope.form = data.form ? data.form : false;
    $scope.$trainingBundle = $trainingBundle;
    $scope.$moment = moment;

    $scope.inscriptionstatus = inscriptionStatusList;
    $scope.presencestatus = angular.copy(presenceStatusList);

    $scope.$watch('inscription.presencestatus', function() {
        if ($scope.inscription.presencestatus && $scope.inscription.presencestatus.id !== 0) {
            $scope.presencestatus[0] = {
                id: 0,
                name: 'Aucun'
            };
        }
        else if ($scope.presencestatus[0] !== undefined) {
            $scope.presencestatus = angular.copy(presenceStatusList);
        }
    });

    /**
     * Open the inscription status dialog
     *
     * @param inscription
     * @param status
     * @returns {promise|*|promise|promise|promise|promise}
     */
    $scope.updateInscriptionStatus = function(status) {
        return $dialog.open('inscription.changeStatus', {
            items: [$scope.inscription.id],
            inscriptionstatus: status,
            presencestatus: undefined
        }).then(function() {
            $scope.inscription.inscriptionstatus = status;
	        $scope.updateActiveItem($scope.inscription);
        });
    }

    /**
     * Open the presence status dialog
     *
     * @param inscription
     * @param status
     * @returns {promise|*|promise|promise|promise|promise}
     */
    $scope.updatePresenceStatus = function(status) {
        return $dialog.open('inscription.changeStatus', {
            items: [$scope.inscription.id],
            presencestatus: status,
            inscriptionstatus: undefined
        }).then(function() {
            $scope.inscription.presencestatus = status;
	        $scope.updateActiveItem($scope.inscription);
        });
    }

    $scope.onSuccess = function(data) {
        $scope.inscription = data.inscription;
        $scope.updateActiveItem($scope.inscription);
    };

    /**
     * Delete
     */
    $scope.delete = function() {
        $dialog.open('inscription.delete', {id: $scope.inscription.id}).then(function() {
            $state.go('inscription.table', {session: $scope.inscription.session.id}, { reload:true });
        });
    }

}]);


