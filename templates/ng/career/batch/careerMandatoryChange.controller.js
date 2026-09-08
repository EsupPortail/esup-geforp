/**
 * Created by maxime on 12/06/14.
 */
/**
 * BatchMailingController
 */
sygeforApp.controller('CareerMandatoryChange', ['$scope', '$http', '$window', '$modalInstance', '$dialogParams', function ($scope, $http, $window, $modalInstance, $dialogParams) {
    $scope.dialog = $modalInstance;
    $scope.items = $dialogParams.items;
    $scope.mandatory = $dialogParams.mandatory;

    $scope.mandatoryOpts = [
        {id: 0, label: 'Facultatif'},
        {id: 1, label: 'Obligatoire'}
    ];

    /**
     *
     */
    $scope.ok = function () {
        var url = Routing.generate('sygefor_core.batch_operation.execute', {id: 'sygefor_career.batch.mandatory_change'});
        var data = {
            options: {
                mandatory: $scope.mandatory
            },
            ids: $scope.items.join(",")
        };
        $http({
            method: 'POST',
            url: url,
            data: data
        }).success(
            function () { //no response expected...
                $scope.dialog.close();
            }
        );
    };

    /**
     *
     */
    $scope.cancel = function () {
        $modalInstance.dismiss();
    }

}]);
