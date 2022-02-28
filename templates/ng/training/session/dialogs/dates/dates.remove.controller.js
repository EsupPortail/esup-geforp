/**
 * Trainer Add Controller
 */
sygeforApp.controller('DatesRemoveController', ['$scope', '$modalInstance', '$dialog', '$dialogParams', '$state', '$http', 'growl', function($scope, $modalInstance, $dialog, $dialogParams, $state, $http, growl) {
    $scope.dialog = $modalInstance;
    $scope.dialog.params = $dialogParams;
    $scope.session = $dialogParams.session;
    $scope.onSuccess = function(data) {
        growl.addSuccessMessage("La date a bien été retirée de la session.");
        $scope.dialog.close(data);
    };

    /**
     * ensures the form was correctly filed (sets an error message otherwise), then asks for server-sid
     */
    $scope.ok = function () {
        var url = Routing.generate('dates.remove', {session: $scope.dialog.params.session.id, dates: $scope.dialog.params.dates.id});
        $http({ method: 'POST', url: url}).success(function (data) {
            growl.addSuccessMessage("La date a bien été retirée de la session.");
            $scope.dialog.close(data);
            $scope.session.dates = $filter('filter')($scope.session.dates, {dates: dates});
        });
        location.reload();
    };
}]);

