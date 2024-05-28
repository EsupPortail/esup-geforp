/**
 * Core List Controller
 */
sygeforApp.controller('DatesViewController', ['$scope', '$dialog', '$filter', '$taxonomy', '$timeout', '$q', function($scope, $dialog, $filter, $form, $taxonomy, $timeout) {

    /**
     * Add dates
     */
    $scope.addDates = function () {
        $dialog.open('dates.add', {session: $scope.session}).then(function (data){
            $scope.session.dates.push(data.date);

        });
    };

    /**
     * Edit dates
     */
    $scope.editDates = function (dates) {
        $dialog.open('dates.edit', {dates: dates}).then(function (data){
            dates = data.dates;
        });
    };

    /**
     * Remove dates
     */
    $scope.removeDates = function (dates) {
        $dialog.open('dates.remove', {session: $scope.session, dates: dates}).then(function (){
            var index = $scope.session.dates.indexOf(dates);
            if (index > -1) {
                $scope.session.dates.splice(index, 1);
            }
        });
    };


}]);
