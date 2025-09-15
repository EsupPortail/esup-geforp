/**
 * Trainer Add Controller
 */
sygeforApp.controller('DatesDuplicateController', ['$scope', '$modalInstance', '$dialog', '$dialogParams', '$state', '$user', '$http', '$window', 'form', 'growl', function ($scope, $modalInstance, $dialog, $dialogParams, $state, $user, $http, $window, form, growl) {

    $scope.dialog = $modalInstance;
    $scope.dialog.params = angular.copy($dialogParams);
    $scope.dates = $dialogParams.dates;
    $scope.form = form;

    /**
     * open an inscription creation window, then process the return by adding inscription
     * @param session
     */
    $scope.duplicateDates = function () {
        $dialog.open('dates.duplicate', {dates: $scope.dates}).then(function (data){
            $scope.session.dates.push(data.dates);
        });
    }

    /**
     *
     * @param data
     */
    $scope.onSuccess = function (data) {
        growl.addSuccessMessage("La date a bien été ajoutée à la session.");
        $scope.dialog.close(data);
        location.reload();
    };
}]);


