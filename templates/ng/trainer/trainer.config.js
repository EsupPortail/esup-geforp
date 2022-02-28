/**
 * TraineeBundle
 */
sygeforApp.config(["$listStateProvider", "$dialogProvider",  function($listStateProvider, $dialogProvider, $dialogParams) {

    // trainee states
    $listStateProvider.state('trainer', {
        url: "/trainer?q&session",
        abstract: true,
        templateUrl: "list.html",
        controller:"TrainerListController",
        breadcrumb: [
            { label: "Intervenants", sref: "trainer.table" }
        ],
        resolve: {
            session: function($stateParams, $entityManager) {
                if($stateParams.session) {
                    return $entityManager('SygeforTrainingBundle:Session\\AbstractSession').find($stateParams.session);
                }
                return null;
            },
            search: function ($searchFactory, $stateParams, $user, session) {
                var search = $searchFactory('trainer.search');
                search.query.sorts = {'lastName.source': 'asc'};
                if(session) {
                    search.filters["sessions.id"] = session.id;
                }
                search.query.filters['organization.name.source'] = $user.organization.name;
                //search.query.filters['isArchived'] = false;
                search.extendQueryFromJson($stateParams.q);
                return search.search().then(function() { return search; });
            }
        },
        states: {
            table: {
                url: "",
                icon: "fa-bars",
                label: "Tableau",
                weight: 0,
                controller: 'ListTableController',
                templateUrl: "mycompanybundle/trainer/states/table/table.html"
            },
            detail: {
                url: "/detail",
                icon: "fa-eye",
                label: "Liste détaillée",
                weight: 1,
                templateUrl: "states/detail/detail.html",
                controller: 'ListDetailController',
                data:{
                    resultTemplateUrl: "mycompanybundle/trainer/states/detail/result.html"
                },
                states: {
                    view: {
                        url: "/:id",
                        templateUrl: "mycompanybundle/trainer/states/detail/trainer.html",
                        controller: 'TrainerDetailViewController',
                        resolve: {
                            data: function($http, $stateParams) {
                                var url = Routing.generate('trainer.view', {id: $stateParams.id});
                                return $http({method: 'GET', url: url}).then (function (data) { return data.data; });
                            }
                        },
                        breadcrumb: {
                            label: "{{ data.trainer.fullName }}"
                        }
                    }
                }
            }
        }
    });

    /**
     * DIALOGS
     */
    $dialogProvider.dialog('trainer.create', /* @ngInject */ {
        templateUrl: 'mycompanybundle/trainer/dialogs/create.html',
        controller: function($scope, $modalInstance, $dialogParams, $state, $http, form, growl) {
            $scope.dialog = $modalInstance;
            $scope.dialog.params = $dialogParams;
            $scope.form = form;
            $scope.onSuccess = function(data) {
                growl.addSuccessMessage("Le formateur a bien été créé.");
                $scope.dialog.close(data);
            };
        },
        resolve:{
            form: function ($http){
                return $http.get(Routing.generate('trainer.create')).then(function (response) {
                    return response.data.form;
                });
            }
        }
    });

    /**
     * trainer deletion modal window
     */
    $dialogProvider.dialog('trainer.delete', /* @ngInject */ {
        templateUrl: 'mycompanybundle/trainer/dialogs/delete.html',
        controller: function($scope, $modalInstance, $dialogParams, $state, $http, growl) {
            $scope.dialog = $modalInstance;
            $scope.dialog.params = $dialogParams;
            $scope.ok = function() {
                var url = Routing.generate('trainer.delete', {id: $dialogParams.trainer.id});
                $http.post(url).then(function (response){
                    $scope.dialog.close();
                    growl.addSuccessMessage("Le formateur a bien été supprimé.");

                });
            };
        }

    });

    /**
     * trainer change organization modal window
     */
    $dialogProvider.dialog('trainer.changeOrg', /* @ngInject */ {
        templateUrl: 'mycompanybundle/trainer/dialogs/change-organization.html',
        controller: function($scope, $modalInstance, $dialogParams, $state, $http, form, growl) {
            $scope.dialog = $modalInstance;
            $scope.dialog.params = $dialogParams;
            $scope.form = form;
            $scope.onSuccess = function(response) {
                growl.addSuccessMessage("Le formateur a bien changé de centre de référence.");
                $scope.dialog.close(response);
            };
        },
        resolve: {
            form: function ($http, $dialogParams){
                return $http.get(Routing.generate('trainer.changeorg', {id: $dialogParams.trainer.id })).then(function(response) {
                    return response.data.form;
                });
            }
        }
    });
}]);


