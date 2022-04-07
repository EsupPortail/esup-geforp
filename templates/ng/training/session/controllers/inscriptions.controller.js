/**
 * Core List Controller
 */
sygeforApp.controller('SessionInscriptionsController', ['$scope', '$dialog', '$filter', '$taxonomy', '$timeout', '$q', function($scope, $dialog, $filter, $taxonomy, $timeout, $q) {

    // initialize status list
    $scope.status = {
        'inscription': [],
        'presence': []
    }

    $scope.filter = {};
    $scope.filterLabel = null;
    $scope.stats = {};

    // fetch all status and count
    $q.all([
/*        $taxonomy.getIndexedTerms('sygefor_inscription.vocabulary_inscription_status'),
        $taxonomy.getIndexedTerms('sygefor_inscription.vocabulary_presence_status')*/
        $taxonomy.getIndexedTerms(9),
        $taxonomy.getIndexedTerms(10)
    ]).then(function(status )  {
        $scope.status.inscription = status[0];
        $scope.status.presence = status[1];
        recalculateStats();
    });

    /**
     * Calculate inscription stats
     */
    var recalculateStats = function() {
        for(var key in $scope.status.inscription) {
            $scope.status.inscription[key].count = $filter('filter')($scope.session.inscriptions, {inscriptionstatus: {id: $scope.status.inscription[key].id }}).length;
        }
        for(var key in $scope.status.presence) {
            $scope.status.presence[key].count = $filter('filter')($scope.session.inscriptions, {presenceStatus: {id: $scope.status.presence[key].id }}).length;
        }
    }

    /**
     * open an inscription creation window, then process the return by adding inscription
     * @param session
     */
    $scope.addInscription = function () {
        $dialog.open('inscription.create', {session: $scope.session}).then(function (data){
            $scope.session.inscriptions.push(data.inscription);
            recalculateStats();
        });
    }

    /**
     * Open the inscription status dialog
     *
     * @param inscription
     * @param status
     * @returns {promise|*|promise|promise|promise|promise}
     */
    $scope.updateInscriptionStatus = function(inscription, status) {
        return $dialog.open('inscription.changeStatus', {
            items: [inscription.id],
            inscriptionstatus: status,
            presencestatus : undefined
        }).then(function() {
            inscription.inscriptionstatus = status;

            recalculateStats();
        });
    }

    /**
     * Open the presence status dialog
     *
     * @param inscription
     * @param status
     * @returns {promise|*|promise|promise|promise|promise}
     */
    $scope.updatePresenceStatus = function(inscription, status) {
        inscription.inscriptionstatus = undefined;
        return $dialog.open('inscription.changeStatus', {
            items: [inscription.id],
            presencestatus: status,
            inscriptionstatus: undefined
        }).then(function() {
            inscription.presencestatus = status;

            recalculateStats();
        });
    }

    /**
     * Remove the inscription
     */
    $scope.delete = function(inscription) {
        $dialog.open('inscription.delete', {id: inscription.id}).then(function() {
            $scope.session.inscriptions.splice($scope.session.inscriptions.indexOf(inscription), 1);
            recalculateStats();
        });
    }


    /**
     * Get the total accepted inscriptions count
     */
    $scope.totalAcceptedInscriptions = function() {
        return $filter('filter')($scope.session.inscriptions, {inscriptionstatus: {status: 2}}).length;
    }

    /**
     * Set the filter to a partiicular status
     */
    $scope.filterByStatus = function(type, status) {
        $scope.resetFilter();
        $scope.filter[type + 'Status'] = {id: status.id };
        $scope.filterLabel = status.name + '(' + status.count + ')';
    }

    /**
     * Reset the filter
     */
    $scope.resetFilter = function() {
        $scope.filter = {};
        $scope.filterLabel = null;
    }
}]);
