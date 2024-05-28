/**
 * Trainer Add Controller
 */
sygeforApp.controller('DatesEditController', ['$scope', '$modalInstance', '$dialog', '$dialogParams', '$state', '$user', '$http', '$window', 'growl', 'data', function ($scope, $modalInstance, $dialog, $dialogParams, $state, $user, $http, $window, growl, data, $moment) {

    $scope.dialog = $modalInstance;
    $scope.dialog.params = $dialogParams;
    $scope.form = data.form;
    $scope.dates = data.dates;
    //$scope.$apply();

    /**
     * open an inscription creation window, then process the return by adding inscription
     * @param dates
     */
    $scope.editDates = function (dates) {
        $dialog.open('dates.edit', {dates: dates}).then(function (data){
            dates = data.dates;
        });
    };

    /**
     *
     * @param data
     */
    $scope.onSuccess = function (data) {
        growl.addSuccessMessage("La date a bien été mise à jour");
        $scope.dialog.close(data);
        location.reload();
    };

}]);


