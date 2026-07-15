sygeforApp.controller('CareerDetailViewController', ['$scope', '$taxonomy', '$dialog', '$http', '$window', '$user', '$state', 'search', 'data', function ($scope, $taxonomy, $dialog, $http, $window, $user, $state, search, data) {

    $scope.career = data.career;
    $scope.form = data.form ? data.form : false;
    $scope.$moment = moment;
    $scope.$user = $user;

    $scope.onSuccess = function (data) {
        $scope.displayNewTrainingCorrespondentForm = false;
        $scope.career = data.career;
        $scope.updateActiveItem($scope.career);
    };

    /**
     * Add a training
     */
    $scope.addTraining = function () {
        $dialog.open('training.add', {career: $scope.career.id}).then(function (data) {
            $scope.career.trainings.push(data.training);
        });
    };

    /**
     * Remove an associated training
     */
    $scope.detachTraining = function (training) {
        $dialog.open('training.remove', {
            training: training,
        }).then(function () {
            var index = $scope.career.trainings.indexOf(training);
            if (index > -1) {
                $scope.career.trainings.splice(index, 1);
            }
        });
    };

    /**
     * Delete the career
     */
    $scope.delete = function () {
        $dialog.open('career.delete', {career: $scope.career}).then(function () {
            $state.go('career.table', null, {reload: true});
        });
    };

}]);
