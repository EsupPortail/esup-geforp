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
            $scope.career.careertrainings.push(data.careertraining);
        });
    };

    /**
     * Remove an associated training
     */
    $scope.detachTraining = function (careertraining) {
        $dialog.open('training.remove', {
            careertraining: careertraining,
        }).then(function () {
            var index = $scope.career.careertrainings.indexOf(careertraining);
            if (index > -1) {
                $scope.career.careertrainings.splice(index, 1);
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

    /**
     * Open the mandatory status dialog
     *
     * @param careertraining
     * @param status
     */
    $scope.updateMandatoryCareer = function (careertraining, status) {
        return $dialog.open('careertraining.changeMandatory', {
            items: [careertraining.id],
            mandatory: status
        }).then(function () {
            careertraining.mandatory = status;
        });
    }

}]);
