/**
 * TrainingBundle
 */
sygeforApp.config(["$listStateProvider", "$dialogProvider", "$widgetProvider", function($listStateProvider, $dialogProvider, $widgetProvider) {

    // session states
    $listStateProvider.state('session', {
        url: "/training/session?q&training&trainers",
        abstract: true,
        templateUrl: "list.html",
        controller:"SessionListController",
        resolve: {
            training: function($stateParams, $entityManager) {
                if($stateParams.training) {
                    return $entityManager('SygeforTrainingBundle:Training\\AbstractTraining').find($stateParams.training);
                }
                return null;
            },
            search: function ($searchFactory, $stateParams, training, $user) {
                var search = $searchFactory('session.search');
                search.query.sorts = {'dateBegin': 'desc'};
                search.query.filters = {
                    'training.organization.name.source': $user.organization.name,
                    'year': moment().format('YYYY'),
                    'semester': Math.ceil(moment().format('M')/6)
                };
                if(training) {
                    search.filters["training.id"] = training.id;
                }
                search.extendQueryFromJson($stateParams.q);
                return search.search().then(function() { return search; });
            }
        },
        breadcrumb: function(training, $trainingBundle) {
            var breadcrumb = [{ label: "Événements", sref: "training.table" }];
            if(training) {
                breadcrumb.push({ label: $trainingBundle.getType(training.type).label, sref: "training.table({type: '" + training.type + "'})" });
                breadcrumb.push({label: training.name, sref: "training.detail.view({id: " + training.id + " })"});
            }
            breadcrumb.push({label: "Sessions", sref: training ? "session.table({training: " + training.id + " })" : "session.table"});
            return breadcrumb;
        },
        states: {
            table: {
                url: "",
                icon: "fa-bars",
                label: "Tableau",
                weight: 0,
                //reloadOnSearch:false,
                controller: 'ListTableController',
                templateUrl: "training/session/states/table/table.html"
            },
            detail: {
                url: "/detail",
                icon: "fa-eye",
                label: "Liste détaillée",
                weight: 1,
                templateUrl: "states/detail/detail.html",
                //reloadOnSearch:false,
                controller: 'ListDetailController',
                data: {
                    resultTemplateUrl: "training/session/states/detail/result.html"
                },
                states: {
                    view: {
                        url: "/:id",
                        templateUrl: "training/session/states/detail/session.html",
                        controller: 'SessionDetailViewController',
                        resolve: {
                            data: function ($http, $stateParams) {
                                var url = Routing.generate('session.view', {id: $stateParams.id});
                                return $http({method: 'GET', url: url}).then(function (data) {
                                    return data.data;
                                });
                            }
                        },
                        breadcrumb: function (data, $filter) {
                            return {label: $filter('date')(data.session.dateBegin, "dd MMMM y")}
                        }
                    }
                }
            }
        }
    });

    /**
     * DIALOGS
     */
    $dialogProvider.dialog('session.create', /* @ngInject */ {
        controller: function($scope, $modalInstance, $dialogParams, $state, $trainingBundle, form, growl) {
            $scope.dialog = $modalInstance;
            $scope.dialog.params = $dialogParams;
            $scope.form = form;

            // add new module option
            if ($scope.form.children.module) {
                $scope.form.children.module.choices.unshift({l: "Nouveau module"});
            }

            $scope.$moment = moment;
            $scope.onSuccess = function(data) {
                growl.addSuccessMessage("La session a bien été créée.");
                $scope.dialog.close(data);
            };
        },
        templateUrl: 'training/session/dialogs/crud/create.html',
        resolve:{
            form: function ($http, $dialogParams){
                return $http.get(Routing.generate('session.create', {training: $dialogParams.training.id })).then(function(response) {
                    return response.data.form;
                });
            }
        }
    });

    $dialogProvider.dialog('session.duplicate', /* @ngInject */ {
        controller: function($scope, $modalInstance, $dialogParams, $state, $trainingBundle, $http, form, growl) {
            $scope.dialog = $modalInstance;
            $scope.dialog.params = $dialogParams;
            $scope.form = form;
            $scope.$moment = moment;
            $scope.session = $dialogParams.session;
            $scope.onSuccess = function(response) {
                growl.addSuccessMessage("La session a bien été dupliquée. Vous êtes à présent sur la fiche de la nouvelle session.");
                $scope.dialog.close(response.session);
            };
        },
        templateUrl: 'training/session/dialogs/crud/duplicate.html',
        resolve:{
            form: function ($http, $dialogParams){
                return $http.get(Routing.generate('session.duplicate', {id: $dialogParams.session.id })).then(function(response) {
                    return response.data.form;
                });
            }
        }
    });

    $dialogProvider.dialog('session.delete', /* @ngInject */ {
        controller: function($scope, $modalInstance, $dialogParams, $state, $trainingBundle, $http, growl) {
            $scope.dialog = $modalInstance;
            $scope.dialog.params = $dialogParams;
            $scope.ok = function() {
                var url = Routing.generate('session.remove', {id: $dialogParams.session.id});
                $http.post(url).then(function (response){
                    growl.addSuccessMessage("La session a bien été supprimée.");
                    $scope.dialog.close(response.data);
                });
            };
        },
        templateUrl: 'training/session/dialogs/crud/delete.html'
    });

    // update programmation dialog to send emails
    $dialogProvider.dialog("session.programmationChange", /* @ngInject */ {
        controller: 'ProgrammationChange',
        templateUrl: 'training/session/batch/ProgrammationChange/programmationChange.html',
        size: 'lg',
        resolve: {
            config: function ($http, $dialogParams) {
                var url = Routing.generate('sygefor_core.batch_operation.modal_config', {service: 'sygefor_mycompany.batch.alert'});
                var optionsArray = {targetClass: 'SygeforMyCompanyBundle:Alert'};

                return $http.get(url, {params: {options: optionsArray}}).then(function (response) {
                    return response.data;
                });
            }
        }
    });

    /**
     * trainer.add: modal for adding a trainer to a session
     */
    $dialogProvider.dialog('trainer.add', /* @ngInject */ {
        templateUrl: 'training/session/dialogs/trainer/trainer-add.html',
        controller: 'TrainerAddController',
        resolve:{
            form: function ($http, $dialogParams){
                return $http.get(Routing.generate('participation.add', {'session': $dialogParams.session.id})).then(function (response) {
                    return response.data.form;
                });
            }
        }
    });

    /**
     * trainer.edit
     */
    $dialogProvider.dialog('participation.edit', /* @ngInject */ {
        templateUrl: 'training/session/dialogs/trainer/participation-edit.html',
        controller: function($scope, $modalInstance, $dialogParams, $http, data, growl) {
            $scope.dialog = $modalInstance;
            $scope.dialog.params = $dialogParams;
            $scope.form = data.form;
            $scope.participation = data.participation;

            $scope.onSuccess = function(data) {
                growl.addSuccessMessage("Les coûts ont été mis à jour.");
                $scope.dialog.close(data);
            };
        },
        resolve:{
            data: function ($http, $dialogParams){
                return $http.get(Routing.generate('participation.edit', {'id': $dialogParams.participation.id})).then(function (response) {
                    return response.data;
                });
            }
        }
    });

    /**
     * trainer.remove : simple confirmation modal for trainer remove
     */
    $dialogProvider.dialog('trainer.remove', /* @ngInject */ {
        templateUrl: 'training/session/dialogs/trainer/trainer-remove.html',
        controller: 'TrainerRemoveController'
    });

    // update status dialog
    $dialogProvider.dialog("session.registrationChange", /* @ngInject */ {
        templateUrl: 'training/session/batch/registrationChange/registrationChange.html',
        controller: 'SessionRegistrationChange'
    });

    /**
     * dates.add: modal for adding dates to a session
     */
    $dialogProvider.dialog('dates.add', /* @ngInject */ {
        templateUrl: 'training/session/dialogs/dates/add.html',
        controller: 'DatesAddController',
        resolve:{
            form: function ($http, $dialogParams){
                return $http.get(Routing.generate('dates.add', {'session': $dialogParams.session.id})).then(function (response) {
                    return response.data.form;
                });
            }
        }
    });

    /**
     * dates.remove : simple confirmation modal for dates remove
     */
    $dialogProvider.dialog('dates.remove', /* @ngInject */ {
        templateUrl: 'training/session/dialogs/dates/remove.html',
        controller: 'DatesRemoveController'
    });

    /**
     * dates.edit
     */
    $dialogProvider.dialog('dates.edit', /* @ngInject */ {
        templateUrl: 'training/session/dialogs/dates/edit.html',
        controller: 'DatesEditController',
        resolve:{
            data: function ($http, $dialogParams){
                return $http.get(Routing.generate('dates.edit', {'dates': $dialogParams.dates.id})).then(function (response) {
                    return response.data;
                });
            }
        }
    });


    /**
     * WIDGETS
     */
    $widgetProvider.widget("session", /* @ngInject */ {
        controller: 'WidgetListController',
        templateUrl: 'training/session/widget/session.html',
        options: function($user) {
            return {
                route: 'session.search',
                rights: ['sygefor_training.rights.training.own.view', 'sygefor_training.rights.training.all.view'],
                state: 'session.table',
                title: 'Prochaines sessions',
                size: 10,
                sorts: {'dateBegin': 'asc'},
                filters: {
                    'training.organization.name.source': $user.organization.name,
                    'dateBegin': moment().format('DD/MM/YYYY') + ' - ' + moment().add('years', 1).format('DD/MM/YYYY')
                }
            }
        }
    });
}]);
