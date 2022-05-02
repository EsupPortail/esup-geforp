/**
 * SessionDetailViewController
 */
sygeforApp.controller('SessionDetailViewController', ['$scope', '$taxonomy', '$dialog', '$trainingBundle', '$user', '$state', '$window','search', 'data', function($scope, $taxonomy, $dialog, $trainingBundle, $user, $state, $window, search, data)
{
    $scope.session = data.session;
    $scope.$trainingBundle = $trainingBundle;
    $scope.form = data.form ? data.form : false;

    /**
     * Get the public_old count
     *
     * @returns int
     */
    $scope.getTotal = function () {
        var total = 0;
        for (var i = 0; i < $scope.session.participantsSummaries.length; i++) {
            total += $scope.session.participantsSummaries[i].count;
        }
        return total;
    };

    /**
     * @param data
     */
    $scope.onSuccess = function(data) {
        $scope.session = data.session;
	    $scope.updateActiveItem($scope.session);
    };

    /**
     * promote
     */
    $scope.promote = function (value) {
        $scope.form.children.promote.checked = !!value;
        $scope.form.submit();
    };

    /**
     * Get nbr of email from entityEmails controller
     */
    $scope.$on('nbrEmails', function(event, value) {
        $scope.session.messages = { length: value };
    });

    /**
     * delete
     */
    $scope.delete = function (){
        $dialog.open('session.delete', {session: $scope.session}).then(function() {
            $state.go('session.table', {training: $scope.session.training.id}, {reload:true});
        });
    };

    /**
     * duplicate
     */
    $scope.duplicate = function() {
        $dialog.open('session.duplicate', {session: $scope.session}).then(function(result){
            $state.go('session.detail.view', {id: result.id}, {reload:true});
        });
    };

    /**
     * Send emails alerts
     */
    $scope.sendAlerts = function () {
        var items = [];
        for (var i=0; i < $scope.session.alerts.length; i++) {
            items.push($scope.session.alerts[i].id);
        }


        $dialog.open('batch.email', {items: items, targetClass: 'App\\Entity\\Alert'});
    };

}]);
