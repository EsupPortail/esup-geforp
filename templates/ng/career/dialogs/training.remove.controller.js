/**
 * Trainer Add Controller
 */
sygeforApp.controller('TrainingRemoveController', ['$scope', '$modalInstance', '$dialog', '$dialogParams', '$state', '$http', 'growl', function ($scope, $modalInstance, $dialog, $dialogParams, $state, $http, growl) {
    $scope.dialog = $modalInstance;
    $scope.dialog.params = $dialogParams;
    $scope.onSuccess = function (data) {
        growl.addSuccessMessage("Le stage a bien été retiré du parcours.");
        $scope.dialog.close(data);
    };

    /**
     * ensures the form was correctly filed (sets an error message otherwise), then asks for server-sid
     */
    $scope.ok = function () {
        var url = Routing.generate('career.detach', {
            careertraining: $scope.dialog.params.careertraining.id,
        });
        $http({method: 'POST', url: url}).success(function (data) {
            growl.addSuccessMessage("Le stage a bien été retiré du parcours.");
            $scope.dialog.close(data);
        });
    };
}]);

