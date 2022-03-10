<div class="search-list full-height-container is-full-width is-absolute">
	<div class="full-height-item">
    	<div list-toolbar></div>
    </div>
    <div class="list-view full-height-item is-grow">
        <div ui-view></div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div widget="session"></div>
        <div widget="inscription" widget-options="options"></div>
    </div>
    <div class="col-md-6">
        <div widget="disclaimer"></div>
        <div widget="trainee"></div>
        <!--<div widget="trainee.duplicate"></div>-->
    </div>
</div>

<div class="widget" ng-class="{'loading': loading}">
    <div class="widget-header">
        <ul class="widget-actions">
            <li ng-if="refresh"><a href="" ng-click="refresh()"><i class="fa fa-refresh"></i></a></li>
            <li ng-if="configure"><a href="" ng-click="configure()"><i class="fa fa-cog"></i></a></li>
            <li ng-if="open"><a href="" ng-click="open()"><i class="fa fa-external-link"></i></a></li>
        </ul>
        <div class="widget-title">{{ options.title }} <small>{{ options.subtitle }}</small></div>
    </div>
    <div class="widget-body-container">
        <div class="widget-loading" ng-show="loading"><i class="fa fa-refresh fa-spin"></i></div>
        <div class="widget-body" widget-body>Chargement...</div>
    </div>
</div>

<div class="list-toolbar">
    <div class="left-group">
        <div class="left-group-container">
            <!-- Select button -->
            <div class="btn-group">
                <button ng-if="!selected.length" ng-click="selectAll()" title="Tous sélectionner" type="button" class="btn btn-default btn-min-width" ng-class="{disabled: !search.result.total}">
                    <i class="fa fa-square-o"></i>
                </button>
                <button ng-if="selected.length" ng-click="deselectAll()" title="Tous désélectionner" type="button" class="btn btn-{{ selected.length > search.result.total ? 'danger' : 'default' }}">
                    <i class="fa fa-check-square-o" ng-if="selected.length == search.result.total"></i>
                    <i class="fa fa-minus-square-o" ng-if="selected.length < search.result.total"></i>
                    <i class="fa fa-warning" ng-if="selected.length > search.result.total" title="{{ selected.length }} eléments sont sélectionnés alors que la dernière recherche a retourné {{ search.result.total }} résultat{{ search.result.total > 1 ? 's' : '' }}"></i>
                </button>

                <button type="button" class="btn btn-{{ selected.length > search.result.total ? 'danger' : 'default' }} dropdown-toggle" data-toggle="dropdown" ng-class="{disabled: !search.result.total}">
                    <span class="caret"></span>
                </button>
                <ul class="dropdown-menu">
                    <li><a href="" ng-click="selectAll()">Tous sélectionner</a></li>
                    <li ng-show="selected.length" class="divider"></li>
                    <li ng-show="selected.length"><a href="" ng-click="deselectAll()" >Tout desélectionner</a></li>
                </ul>
            </div>

            <!-- Mass operations -->
            <div class="btn-group btn-mass-operation">
                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" ng-class="{disabled: !selected.length}">
                    Action groupée <span class="caret"></span>
                </button>
                <ul class="dropdown-menu">
                    <li ng-repeat="operation in batchOperations| filter: batchOperationAvailable" ng-class="{'dropdown-submenu': operation.subitems}">
                        <!-- with subitems -->
                        <a href="" ng-if="operation.subitems"><i class="fa {{ operation.icon }}"></i> {{ operation.label }}</a>
                        <ul class="dropdown-menu" ng-if="operation.subitems">
                            <li ng-repeat="sub in operation.subitems"><a href="" ng-click="batch(sub)"><i class="fa {{ sub.icon }}"></i> {{ sub.label }}</a></li>
                        </ul>
                        <!-- without subitems -->
                        <a href="" ng-click="batch(operation)" ng-if="!operation.subitems"><i class="fa {{ operation.icon }}"></i> {{ operation.label }}</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Search bar -->
    <div class="center-group form-group">
        <div class="center-group-container">
            <!--input type="search" class="form-control"  placeholder="Rechercher..." ng-model="search.query.keywords"-->
            <div search-box ng-controller="SearchBoxController" placeholder="Cliquez pour filtrer la liste..." facet-list="sbfacets" result-list="sbparameters"></div>
        </div>
    </div>

    <!-- View switch -->
    <div class="right-group">
        <div class="right-group-container btn-group">
            <span ng-show="!!search.result.total" class="btn btn-default btn-deactivated"><strong>{{ (search.query.page-1)*search.query.size + 1  }} - {{ search.query.page*search.query.size < search.result.total ? search.query.page*search.query.size : search.result.total  }}</strong> sur <strong>{{ search.result.total }}</strong></span><!--
            --><button ng-repeat="state in $state.$current.self.root.states|orderObjectBy : 'weight'" type="button" ng-attr-title="Visualiser en mode : {{ state.label }}" class="btn btn-default" ng-class="{active: $state.includes(state.name)}" ng-click="$state.go(state.name)"><i class="fa {{ state.icon }}"></i></button>
        </div>
    </div>

    <!-- add button -->
    <div class="btn-add-operation">
        <div class="btn-add-operation-container">
            <div class="btn-group">
                <!-- <button type="button" class="btn btn-default" ng-click="addOperation(addOperations[0])"><i class="fa {{ addOperations[0].label }}"></i></button>
                <div ng-switch-default> -->
                <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" ng-class="{disabled: (addOperations | filter: addOperationAvailable).length == 0 }">
                    Ajouter <span class="caret"></span>
                </button>
                <ul class="dropdown-menu">
                    <li ng-repeat="operation in addOperations | filter: addOperationAvailable"><a href="" ng-click="operation.execute(operation.key)"><i class="fa {{ operation.icon }}"></i> {{ operation.label }}</a></li>
                </ul>
            </div>
        </div>
    </div>

</div>

<div class="modal-header">
    <button type="button" class="close" ng-click="dialog.dismiss()">×</button>
    <h4 class="modal-title">Etes-vous sûr de vouloir convertir ces {{ items.length }} formations vers le type {{ getTypeLabel() }} ?</h4>
</div>

<div class="modal-footer">
    <a class="btn btn-default"  ng-click="dialog.dismiss()">Annuler</a>
    <a class="btn btn-primary" ng-click="ok()">Convertir les formations</a>
</div>

<div class="modal-header">
    <button type="button" class="close" ng-click="modalInstance.dismiss()">×</button>
    <h4 class="modal-title">Prévisualisation du message :</h4>
</div>
<div class="modal-body">
    <div class="form-horizontal">
        <div class="form-group">
            <label class="col-sm-3 control-label">Sujet</label>
            <div class="col-md-9">
                <div class="form-control" style="height: auto">
                    {{ email.subject }}
                </div>
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-3 control-label">Message</label>
            <div class="col-md-9">
                <div ng-bind-html="email.message | nl2br" class="form-control" style="height: auto"></div>
            </div>
        </div>
        <div class="form-group" ng-if="email.templateAttachments.length > 0">
            <label class="col-sm-3 control-label">Pièce{{ email.templateAttachments.length > 0 ? 's' : '' }} jointe{{ email.templateAttachments.length > 0 ? 's' : '' }} du modèle</label>
            <div class="col-md-9">
                <div ng-repeat="templateAttachment in email.templateAttachments" class="form-control" style="height: auto">
                    <span>{{ templateAttachment }}</span>
                </div>
            </div>
        </div>

        <div class="form-group" ng-if="email.attachments.length > 0">
            <label class="col-sm-3 control-label">Pièce{{ email.attachments.length > 1 ? 's' : '' }} jointe{{ email.attachments.length > 1 ? 's' : '' }} supplémentaire{{ email.attachments.length > 1 ? 's' : '' }}</label>
            <div class="col-md-9">
                <div ng-repeat="attachment in email.attachments" class="form-control" style="height: auto">
                    <span>{{ attachment.name }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <a class="btn btn-primary" ng-click="modalInstance.dismiss()">Fermer</a>
</div>

<div class="modal-header">
    <button type="button" class="close" ng-click="dialog.dismiss()">×</button>
    <h4 class="modal-title"><i class="fa fa-envelope-o"></i> Envoyer un email</h4>
</div>
<div class="modal-body">
    <form novalidate class="form-horizontal" role="form">
        <div class="form-group">
            <label class="col-sm-2 control-label">Destinataires </label>
            <div class="col-sm-10">
                <span class="form-control">{{ items.length }}</span>
            </div>
        </div>
        <div class="form-group" ng-if="templates.length">
            <label for="subject" class="col-sm-2 control-label">Modèle </label>
            <div class="col-sm-10">
                <select type="text" class="form-control" id="template" ng-options="template.label for template in templates" ng-model="message.template" placeholder="Modèle du mail">
                </select>
            </div>
        </div>
        <div class="form-group">
            <label for="subject" class="col-sm-2 control-label">Sujet </label>
            <div class="col-sm-10">
                <input type="text" class="form-control" id="subject" ng-model="message.subject" placeholder="Sujet du mail" autofocus>
            </div>
        </div>
        <div class="form-group">
            <label for="message" class="col-sm-2 control-label">Message </label>
            <div class="col-sm-10">
                <textarea class="form-control" rows="10" id="message" ng-model="message.body"  placeholder="Message du mail"></textarea>
            </div>
        </div>
        <div class="form-group" ng-if="message.templateAttachments.length > 0">
            <label for="attachmentTemplate" class="col-sm-2 control-label">Pièces jointes du modèle</label>
            <div class="col-sm-10">
                <div ng-if="attachmentTemplate && isAObject(attachmentTemplate)" ng-repeat="attachmentTemplate in message.templateAttachments track by $index">
                    <input type="checkbox" ng-model="message.templateAttachmentChecklist[attachmentTemplate.id]" ng-checked="true" id="attachmentTemplate_{{ attachmentTemplate.id }}">
                    <label class="control-label" for="attachmentTemplate_{{ attachmentTemplate.id }}">{{attachmentTemplate.name}}</label>&nbsp;
                </div>
            </div>
        </div>
        <div class="form-group" ng-if="message.attachments.length > 0">
            <label for="attachments" class="col-sm-2 control-label">Pièce{{ message.attachments.length > 1 ? 's' : '' }} jointe{{ message.attachments.length > 1 ? 's' : '' }} supplémentaire{{ message.attachments.length > 1 ? 's' : '' }}</label>
            <div class="col-sm-10">
                <div ng-repeat="(id, attachment) in message.attachments track by $index">
                    <label class="control-label" for="attachment_{{ id }}">{{attachment.name}}</label>&nbsp;
                    <a href="" ng-click="removeAttachment(id)">&nbsp;x&nbsp;</a>
                </div>
            </div>
        </div>
        <div class="form-group">
            <label for="inputTplFile" class="col-sm-2 control-label">Ajouter des pièces jointes</label>
            <div class="col-sm-10">
                <input multiple type="file" class="form-control" onChange="angular.element(this).scope().fileChanged(this,angular.element(this).scope())" id="inputAttachment"/>
            </div>
        </div>
        <div class="form-group" >
            <div class="col-sm-2">
            </div>
            <div class="col-sm-10">
                <button type="button" class="btn" ng-click="preview()">Prévisualiser</button>
            </div>
        </div>
        <div class="alert alert-danger" ng-show="formError != ''">{{ formError }}</div>
    </form>
</div>
<div class="modal-footer">
    <a class="btn btn-default" ng-click="dialog.dismiss()">Annuler</a>
    <a class="btn btn-primary" ng-click="ok()">Envoyer</a>
</div>
<div class="modal-header">
    <button type="button" class="close" ng-click="cancel()">×</button>
    <h4 class="modal-title">Mailing</h4>
</div>
<div class="modal-body">
    <form novalidate class="form-horizontal" role="form">
        <div class="form-group">
            <label class="col-sm-2 control-label">Nombre de destinataires </label>
            <div class="col-sm-10">
                <span class="form-control">{{ selected.length }}</span>
            </div>
        </div>
        <div class="form-group">
            <label for="subject" class="col-sm-2 control-label">Sujet </label>
            <div class="col-sm-10">
                <input type="text" class="form-control" id="subject" ng-model="options.subject" placeholder="Sujet du mail">
            </div>
        </div>
        <div class="form-group">
            <label for="message" class="col-sm-2 control-label">Message </label>
            <div class="col-sm-10">
                <textarea class="form-control" rows="10" id="message" ng-model="options.message"  placeholder="Message du mail"></textarea>
            </div>
        </div>
    </form>
</div>
<div class="modal-footer">
    <a class="btn btn-default" ng-click="cancel()">Annuler</a>
    <a class="btn btn-primary" ng-click="ok()">Envoyer</a>
</div>


<div class="modal-header">
    <button type="button" class="close" ng-click="dialog.dismiss()">×</button>
    <h4 class="modal-title">Publipostage</h4>
</div>

<div class="modal-body">
    <form novalidate class="form-horizontal" role="form" enctype="multipart/form-data">
        <div class="form-group">
            <label class="col-sm-4 control-label">Nombre d'éléments </label>
            <div class="col-sm-8">
                <span class="form-control">{{ items.length }}</span>
            </div>
            </div>
            <div class="form-group">
                <label for="inputTplName" class="col-sm-4 control-label">Modèles disponibles </label>
                <div class="col-sm-8">
                    <select type="text" class="form-control" id="inputTplName" ng-model="options.template" ng-options="value as value.name for value in templateList"></select>
                </div>
            </div>
            <div class="form-group">
                <label for="inputTplFile" class="col-sm-4 control-label">Choisir un modèle </label>
                <div class="col-sm-6">
                    <input type="file" class="form-control" onChange="angular.element(this).scope().fileChanged(this,angular.element(this).scope())" id="inputTplFile" ng-model="options.templateFile" />
                </div>
                <div class="col-sm-2 ">
                    <button class="btn btn-primary" ng-click="resetUpload(angular.element($('#inputTplFile')))"><i class="fa fa-undo fa-2g"></i></button>
                </div>
            </div>
            <div class="form-group">
                <div class="col-sm-4"></div>
                <div class="col-sm-4">
                    <input type="checkbox" id="sendPdf" ng-model="options.sendPdf" />
                    <label for="sendPdf" class="control-label">Générer en pdf</label>
                </div>
            </div>
            <div class="alert alert-danger" ng-show="chooseError != ''">{{ chooseError }}</div>
        </form>
    </div>
</div>

<div class="modal-footer">
    <a class="btn btn-default"  ng-click="dialog.dismiss()">Annuler</a>
    <a class="btn btn-primary" ng-click="ok()">Créer le document</a>
</div>

<div class="full-height-container is-absolute is-full-width is-direction-row">

    <div class="full-height-item grid-list-detail-results">

        <div class="full-height-container is-full-width is-absolute is-direction-column">

            <div class="full-height-item is-full-width is-grow is-overflow-y">
                <div class="list-group" ng-if="search.result.items.length">
                    <div class="list-group-item " ng-repeat="result in search.result.items"
                         ng-class="[result.class, {active: $stateParams.id == result.id, 'list-group-item-warning': selected.indexOf(result.id) > -1}]">
                        <i class="fa" ng-click="switchSelect(result.id)"
                           ng-class="{'fa-square-o': !isSelected(result.id), 'fa-check-square-o': isSelected(result.id)}"></i>

                        <div ng-include="resultTemplateUrl"></div>
                    </div>
                </div>
            </div>

            <div class="full-height-item is-full-width pager-wrapper">
                <!-- <pager total-items="search.result.total" ng-model="search.query.page"></pager> -->
                <pagination ng-if="search.result.total > 0" ng-model="search.query.page"
                            total-items="search.result.total" items-per-page="search.query.size" direction-links="false"
                            boundary-links="false" rotate="false" max-size="4" next-text=">" previous-text="<"
                            first-text="Début" last-text="Fin"></pagination>
            </div>
        </div>

    </div>


    <div class="full-height-item grid-list-detail-view"
         ng-class="{'': search.result.items.length, 'col-lg-12': !search.result.items.length}">
        <div class="full-height-container is-full-width is-absolute is-direction-column">

            <div class="full-height-item is-overflow-y is-grow view">
                <!-- <div class="absolute-fixed-wrapper"> -->
                <div class="ui-view-container">
                    <div class="col-xs-12">
                        <div ui-view></div>
                    </div>
                </div>
                <!-- </div> -->
            </div>
            <div ng-if="$state.current.views.bottom" class="fixed-bloc-wrapper full-height-item">
                <div ui-view="bottom">

                </div>
            </div>

        </div>
    </div>


</div>

<div class="modal-header">
    <button type="button" class="close" ng-click="dialog.dismiss()">×</button>
    <h4 class="modal-title">Exporter en CSV</h4>
</div>
<div class="modal-body">
    <form novalidate class="form-horizontal" role="form">
        <div class="form-group">
            <label class="col-sm-4 control-label">Nombre de lignes </label>
            <div class="col-sm-8">
                <span class="form-control">{{ items.length }}</span>
            </div>
        </div>
        <div class="form-group">
            <label for="inputEmail3" class="col-sm-4 control-label">Delimiter </label>
            <div class="col-sm-8">
                <input type="text" class="form-control" id="inputEmail3" ng-model="options.delimiter" placeholder=";">
            </div>
        </div>
    </form>
</div>
<div class="modal-footer">
    <a class="btn btn-default"  ng-click="dialog.dismiss()">Annuler</a>
    <a class="btn btn-primary" ng-click="export()">Exporter</a>
</div>

<div class="modal-header">
    <button type="button" class="close" ng-click="dialog.dismiss()">×</button>
    <h4 class="modal-title"><i class="fa fa-file-pdf-o"></i> Exporter en PDF</h4>
</div>
<div class="modal-body">
    <form novalidate class="form-horizontal" role="form">
        <div class="form-group">
            <label class="col-sm-4 control-label">Nombre d'éléments</label>
            <div class="col-sm-8">
                <span class="form-control">{{ items.length }}</span>
            </div>
        </div>
    </form>
</div>
<div class="modal-footer">
    <a class="btn btn-default" ng-click="dialog.dismiss()">Annuler</a>
    <a class="btn btn-primary" ng-click="download()">Télécharger</a>
</div>

<div>
    <div class="mb-1">

        <div ng-if="!inscription.presences.length" class="well well-empty well-sm">
            Il n'y a aucune présence pour cette session.
        </div>

        <table ng-if="inscription.presences.length" class="table table-hover table-search table-condensed">
            <thead>
            <tr>
                <th>Date</th>
                <th>Matin</th>
                <th>Après-midi</th>
                <th>Modifier</th>
            </tr>
            </thead>
            <tbody>
            <tr ng-repeat="presence in inscription.presences | orderBy:'dateBegin':false" >
                <!--form sf-xeditable-form="form" sf-href='dates.edit({dates: date })' on-success="onSuccess(data)"-->
                    <td>{{ presence.dateBegin|date: 'dd/MM/yyyy' }}</td>
                    <td>{{ presence.morning }}</td>
                    <td>{{ presence.afternoon }}</td>
                    <td><a class="btn btn-fa" href="" ng-click="editPresence(presence)" tooltip="Modifier"><span class="fa fa-pencil"></span></a></td>
                <!--/form-->
            </tr>
            </tbody>
        </table>

    </div>

</div>

<div>
    <div ng-if="!search.result.total" class="well well-empty well-sm">
        Aucun message n'a été envoyé à ce stagiaire.
    </div>

    <table ng-if="search.result.total" class="table table-hover table-responsive table-search.result table-condensed table-nohead">
        <!--<thead>-->
            <!--<tr>-->
                <!--<th>Sujet</th>-->
                <!--<th>Session</th>-->
                <!--<th>Envoyé par</th>-->
                <!--<th>Date</th>-->
            <!--</tr>-->
        <!--</thead>-->
        <tbody>
        <tr ng-repeat="email in search.result.items">
            <td><a title="Voir le message" ng-click="dislayEmail(email.id)" href="">{{ email.subject }}</a></td>
            <td><a title="Voir la session" href="" ui-sref="session.detail.view({ id: email.session.id })">{{ email.session.training.name }}</a></td>
            <td title="Envoyé par {{ email.userFrom.username }}">{{ email.userFrom.username }}</td>
            <td title="Date d'envoi">{{ email.sendAt | date: 'dd/MM/yyyy' }}</td>
        </tr>
        </tbody>
    </table>
    <div ng-if="search.result.total > search.query.size" class="full-height-item is-full-width">
        <div search-table-controls></div>
    </div>
</div>

<div>
    <h3><span class="fa fa-send"></span> Messages envoyés <span class="badge">{{ search.result.total ? search.result.total : 0 }}</span></h3><hr/>

    <div ng-if="!search.result.total" class="well well-empty well-sm">
        Aucun message n'a été envoyé à ce formateur.
    </div>

    <table ng-if="search.result.total" class="table table-hover table-responsive table-search.result table-condensed table-nohead">
        <!--thead>
            <tr>
                <th>Sujet</th>
                <th>Envoyé par</th>
                <th>Date</th>
            </tr>
        </thead-->
        <tbody>
        <tr ng-repeat="email in search.result.items">
            <td><a title="Voir le message" ng-click="dislayEmail(email.id)" href="">{{ email.subject }}</a></td>
            <td title="Envoyé par {{ email.userFrom.username }}">{{ email.userFrom.username }}</td>
            <td title="Date d'envoi">{{ email.sendAt | date: 'dd/MM/yyyy' }}</td>
        </tr>
        </tbody>
    </table>
    <div ng-if="search.result.total > search.query.size" class="full-height-item is-full-width">
        <div search-table-controls></div>
    </div>
</div>

<form sf-href="material.add({entity_id: dialog.params.entity_id, type_entity: dialog.params.entityType, material_type: dialog.params.material_type})" sf-form="form" json-path="form" on-success="onSuccess(data)" class="form-horizontal">
<div class="modal-header">
    <button type="button" class="close" ng-click="dialog.dismiss()" aria-hidden="true">×</button>
    <h4 class="modal-title" id="myModalLabel">Ajouter un lien</h4>
</div>
<div class="modal-body">
    <div ng-repeat="key in ['name', 'url']">
        <div class="form-group" ng-if="form.children[key]" ng-class="{'has-error': form.children[key].errors.length }">
            <label class="col-sm-3 control-label" for="{{ form.children[key].id }}">{{ form.children[key].label }}</label>
            <div class="col-sm-9">
                <span sf-form-widget="form.children[key]" class="form-control"/>
                <div ng-repeat="error in form.children[key].errors" class="help-block">{{ error }}</div>
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <a class="btn btn-default" ng-click="dialog.dismiss()">Fermer</a>
    <input class="btn btn-primary" type="submit" value="Ajouter" />
</div>
</form>
<div class="modal-header">
    <button type="button" class="close" ng-click="dialog.dismiss()" aria-hidden="true">×</button>
    <h4 class="modal-title" id="myModalLabel">Gérer les supports</h4>
</div>

<div class="modal-body">
    <!--add buttons-->
    <div class="row mb-1 fileupload-buttonbar">
        <div class="col-xs-12">
            <div class="pull-right">

                <!--file add button-->
                <span class="btn btn-primary fileinput-button">
                    <i class="glyphicon glyphicon-plus"></i>
                    <span>Ajouter un fichier</span>
                    <div sf-file-upload data-thref="{route: 'material.add', params: {entity_id: dialog.params.entity.id, type_entity: dialog.params.entityType}}"
                         data-add-callback="getUploadedFile"
                         data-queue='entity.materials'>
                    </div>
                </span>

                <!--link material add button-->
                <span class="btn btn-primary" ng-click="addLinkMaterial()">
                    <i class="glyphicon glyphicon-plus"></i>
                    <span>Ajouter un lien</span>
                </span>
            </div>
        </div>
    </div>

    <!-- The list of files availables for upload/download -->
    <div ng-if='!entity.materials.length' class="row">
        <div class="col-xs-12">
            <div class="well well-empty well-sm">
                Aucun support n'est disponible
            </div>
        </div>
    </div>

    <div ng-if='entity.materials.length' ng-repeat="file in entity.materials track by $index" class="row mb-1">
        <div class="col-md-6">
            <button type="button" class="btn btn-default">
                {{file.name}}
            </button>
        </div>

        <div class="col-md-3">
            <!--link material-->
            <a ng-if="file.url" target="_blank" href="{{file.url}}"><i class="fa fa-external-link"></i></a>

            <!--file material uploading -->
            <div ng-if="file.uploading" class="progress progress-striped active fade" ng-class="{pending: 'in'}[file.$state()]" data-file-upload-progress="file.$progress()" style="width:96px; height:33px;">
                <div class="progress-bar progress-bar-success" ng-style="{width: num + '%'}"></div>
            </div>

            <!--file material on server -->
            <button ng-if="file.filePath" type="button" class="btn btn-success" ng-click="getFile(file)">
                <i class="fa fa-download"></i>
                <span>Voir</span>
            </button>
        </div>

        <div class="col-md-3">
            <!--link material or server file-->
            <div ng-if="file.url || file.filePath" class="col-md-3">
                <button type="button" class="btn btn-warning cancel" ng-click="removeMaterial(file)">
                    <i class="glyphicon glyphicon-ban-circle"></i>
                    <span>Retirer</span>
                </button>
            </div>

            <!--file material uploading -->
            <div ng-if="file.uploading" class="col-md-3">
                <button type="button" class="btn btn-warning cancel" ng-click="file.$cancel()"
                        ng-hide="!file.$cancel">
                    <i class="glyphicon glyphicon-ban-circle"></i>
                    <span>Arreter</span>
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal-footer">
    <a class="btn btn-default" ng-click="dialog.dismiss()">Fermer</a>
</div>

<div class="modal-header">
    <button type="button" class="close" ng-click="dialog.dismiss()" aria-hidden="true">×</button>
    <h4 class="modal-title"><i class="fa fa-warning"></i> Supprimer un support</h4>
</div>
<div class="modal-body center-block">
    <div class="alert alert-danger" role="alert">
        Êtes-vous certain de vouloir supprimer définitivement le support <strong>{{ dialog.params.material.name }}</strong> ?
    </div>
</div>
<div class="modal-footer">
    <a class="btn btn-default" ng-click="dialog.dismiss()">Annuler</a>
    <a class="btn btn-primary" ng-click="ok()">Valider</a>
</div>

<div class="modal-header">
    <button type="button" class="close" ng-click="dialog.dismiss()" aria-hidden="true">×</button>
    <h4 class="modal-title" id="myModalLabel">Créer une session</h4>
</div>

<form sf-href="session.create({training: dialog.params.training.id})" sf-form="form" json-path="form" on-success="onSuccess(data)" class="form-horizontal" novalidate>
    <div class="modal-body">

        <div ng-repeat="key in ['name', 'module']">
            <div class="form-group" ng-if="form.children[key]" ng-class="{'has-error': form.children[key].errors.length }">
                <label class="col-sm-3 control-label" for="{{ form.children[key].id }}">{{ form.children[key].label }}</label>
                <div class="col-sm-9">
                    <span sf-form-widget="form.children[key]" class="form-control"/>
                    <div ng-if="error.length" ng-if="error.length" ng-repeat="error in form.children[key].errors" class="help-block">{{ error }}</div>
                </div>
            </div>
        </div>

        <div ng-show="form.children['module'] && !form.children['module'].value" class="form-group">
            <label class="col-sm-3 control-label" for="form.children['newModule'].children.name.id">Nouveau module</label>
            <div class="col-sm-9">
                <div class="row">
                    <span ng-class="{'has-error': form.children['newModule'].children.name.errors.length }" class="col-sm-10">
                        <input type="text" class="form-control" ng-model="form.children['newModule'].children.name.value" />
                    </span>
                    <span class="col-sm-2">
                        <span title="{{ form.children['newModule'].children.mandatory.label }}" sf-form-widget="form.children['newModule'].children.mandatory" class="form-control"/>
                    </span>
                </div>
                <span ng-class="{'has-error': form.children['newModule'].children.name.errors.length + form.children['newModule'].children.mandatory.errors }">
                    <div ng-if="error.length" ng-repeat="error in form.children['newModule'].children.name.errors" class="help-block">{{ error }}</div>
                    <div ng-if="error.length" ng-repeat="error in form.children['newModule'].children.mandatory.errors" class="help-block">{{ error }}</div>
                </span>
            </div>
        </div>

        <div class="form-group">
            <label class="col-sm-3 control-label" for="form.children.dateBegin.id">Date(s)</label>
            <div class="col-sm-9">
                <div class="controls form-inline input-daterange" bs-datepicker bs-datepicker-view-date="{{ $moment().format('YYYY-MM-DD') }}">
                    <span ng-class="{'has-error': form.children.dateBegin.errors.length }">
                        <input type="text" class="form-control input-datepicker" ng-model="form.children.dateBegin.value" />
                    </span>
                    <label>au</label>
                    <span ng-class="{'has-error': form.children.dateEnd.errors.length }">
                        <input type="text" class="form-control input-datepicker" ng-model="form.children.dateEnd.value" />
                    </span>
                </div>
                <span ng-class="{'has-error': form.children.dateBegin.errors.length+form.children.dateEnd.errors }">
                    <div ng-if="error.length" ng-repeat="error in form.children.dateBegin.errors" class="help-block">{{ error }}</div>
                    <div ng-if="error.length" ng-repeat="error in form.children.dateEnd.errors" class="help-block">{{ error }}</div>
                </span>
            </div>
        </div>

        <div class="form-group" ng-class="{'has-error': form.children.maximumNumberOfRegistrations.errors.length }">
            <label class="col-sm-3 control-label" for="{{ form.children.maximumNumberOfRegistrations.id }}">{{ form.children.maximumNumberOfRegistrations.label }}</label>
            <div class="col-sm-3">
                <span sf-form-widget="form.children.maximumNumberOfRegistrations" class="form-control"/>
                <div ng-if="error.length" ng-repeat="error in form.children.maximumNumberOfRegistrations.errors" class="help-block">{{ error }}</div>
            </div>
        </div>

        <!-- inscription status -->
        <!--div class="form-group" ng-init="childReg = form.children.registration" ng-class="{'has-error': childReg.errors.length }">
            <label class="col-sm-3 control-label" for="{{ childReg.id }}">{{childReg.label }}</label>
            <div class="col-sm-3">
                <span sf-form-widget="childReg" class="form-control"/>
                <div ng-if="error.length" ng-repeat="error in childReg.errors" class="help-block">{{ error }}</div>
            </div>
        </div-->

        <div class="form-group" ng-init="childDislayOnline = form.children.displayOnline" ng-class="{'has-error': childDislayOnline.errors.length }">
            <label class="col-sm-3 control-label" for="{{ childDislayOnline.id }}">{{ childDislayOnline.label }}</label>
            <div class="col-sm-9">
                <label ng-repeat="(key,choice) in childDislayOnline.choices">
                    <input type="radio" name="{{ childDislayOnline.name }}" value="{{ choice.v }}" ng-model="form.children.displayOnline.value"> {{ choice.l }}&nbsp;&nbsp;&nbsp;
                </label>
                <div ng-if="error.length" ng-repeat="error in dateBegin.errors" class="help-block">{{ error }}</div>
            </div>
        </div>


        <div class="form-group" ng-init="childReg = form.children.registration" ng-class="{'has-error': childReg.errors.length }">
            <label class="col-sm-3 control-label" for="{{ childReg.id }}">{{ childReg.label }}</label>
            <div class="col-sm-9">
                <div ng-repeat="(key,choice) in childReg.choices">
                    <label>
                        <input type="radio" name="{{ childReg.name }}" value="{{ choice.v }}" ng-model="form.children.registration.value"> {{ choice.l }}
                        <div class="help-block" ng-if="choice.v == '0'">Les inscriptions ne sont pas gérées.</div>
                        <div class="help-block" ng-if="choice.v == '1'">Les inscriptions sont gérées mais fermées au public.</div>
                        <div class="help-block" ng-if="choice.v == '2'">Les inscriptions sont gérées et accessibles à un public restreint.</div>
                        <div class="help-block" ng-if="choice.v == '3'">Les inscriptions sont gérées et accessibles publiquement.</div>
                    </label>
                </div>
                <div ng-if="error.length" ng-repeat="error in childReg.errors" class="help-block">{{ error }}</div>
            </div>
        </div>

        <div ng-repeat="key in ['dayNumber', 'hourNumber']">
            <div class="form-group" ng-if="form.children[key]" ng-class="{'has-error': form.children[key].errors.length }">
                <label class="col-sm-3 control-label" for="{{ form.children[key].id }}">{{ form.children[key].label }}</label>
                <div class="col-sm-9">
                    <span sf-form-widget="form.children[key]" class="form-control"/>
                    <div ng-if="error.length" ng-if="error.length" ng-repeat="error in form.children[key].errors" class="help-block">{{ error }}</div>
                </div>
            </div>
        </div>


    </div>
    <div class="modal-footer">
        <a class="btn btn-default" ng-click="dialog.dismiss()">Annuler</a>
        <input class="btn btn-primary" type="submit" value="Enregistrer" />
    </div>
</form>

<div class="modal-header">
    <button type="button" class="close" ng-click="dialog.dismiss()" aria-hidden="true">×</button>
    <h4 class="modal-title"><i class="fa fa-warning"></i> Supprimer une session</h4>
</div>
<div class="modal-body center-block">
    <div class="alert alert-danger" role="alert">
        Êtes-vous certain de vouloir supprimer la session <strong>{{ dialog.params.session.name }} du {{ dialog.params.session.dateBegin|date }}</strong> de la formation <strong>{{ dialog.params.session.training.name }}</strong> ?<br/>
        Attention cette action supprimera les inscriptions à cette session !
    </div>
</div>
<div class="modal-footer">
    <a class="btn btn-default" ng-click="dialog.dismiss()">Annuler</a>
    <a class="btn btn-primary" ng-click="ok()">Valider</a>
</div>

<div class="modal-header">
    <button type="button" class="close" ng-click="dialog.dismiss()" aria-hidden="true">×</button>
    <h4 class="modal-title"><i class="fa fa-copy"></i> Dupliquer une session</h4>
</div>
<form sf-href="session.duplicate({id: session.id, inscriptionIds: inscriptions})" sf-form="form" json-path="form"
      on-success="onSuccess(data)" class="form-horizontal" novalidate>
    <div class="modal-body center-block">
        <div class="form-group" ng-if="form.children['name']" ng-class="{'has-error': form.children['name'].errors.length }">
            <label class="col-sm-3 control-label" for="{{ form.children['name'].id }}">{{ form.children['name'].label }}</label>
            <div class="col-sm-9">
                <span sf-form-widget="form.children['name']" class="form-control"/>
                <div ng-if="error.length" ng-if="error.length" ng-repeat="error in form.children['name'].errors" class="help-block">{{ error }}</div>
            </div>
        </div>
        
        <div class="form-group">
            <label class="col-sm-3 control-label" for="form.children.dateBegin.id">Date(s)</label>
            <div class="col-sm-9">
                <div class="controls form-inline input-daterange" bs-datepicker bs-datepicker-view-date="{{ $moment().add('month', 6).year() }}-{{ $moment().add('month', 6).quarter() < 3 ? '01' : '07' }}-01">
                    <span ng-class="{'has-error': form.children.dateBegin.errors.length }">
                        <input type="text" class="form-control input-datepicker" ng-model="form.children.dateBegin.value" />
                    </span>
                    <label>au</label>
                    <span ng-class="{'has-error': form.children.dateEnd.errors.length }">
                        <input type="text" class="form-control input-datepicker" ng-model="form.children.dateEnd.value" />
                    </span>
                </div>
                <span ng-class="{'has-error': form.children.dateBegin.errors.length+form.children.dateEnd.errors }">
                    <div ng-if="error.length" ng-repeat="error in form.children.dateBegin.errors" class="help-block">{{ error }}</div>
                    <div ng-if="error.length" ng-repeat="error in form.children.dateEnd.errors" class="help-block">{{ error }}</div>
                </span>
            </div>
        </div>

        <div class="form-group" ng-if="form.children['inscriptionManagement']" ng-class="{'has-error': form.children['inscriptionManagement'].errors.length }">
            <label class="col-sm-3 control-label" for="{{ form.children['inscriptionManagement'].id }}">{{ form.children['inscriptionManagement'].label }}</label>
            <div class="col-sm-9">
                <span sf-form-widget="form.children['inscriptionManagement']" class="form-control"/>
                <div ng-if="error.length" ng-if="error.length" ng-repeat="error in form.children['inscriptionManagement'].errors" class="help-block">{{ error }}</div>
            </div>
        </div>

    </div>
    <div class="modal-footer">
        <a class="btn btn-default" ng-click="dialog.dismiss()">Annuler</a>
        <input class="btn btn-primary" type="submit" value="Valider" />
    </div>
</form>




<form sf-href="dates.add({session: dialog.params.session.id})" sf-form="form" json-path="form" on-success="onSuccess(data)" class="form-horizontal" novalidate>
    <div class="modal-header">
        <button type="button" class="close" ng-click="dialog.dismiss()" aria-hidden="true">×</button>
        <h4 class="modal-title"><i class="fa fa-graduation-cap"></i> Ajouter une date</h4>
    </div>
    <div class="modal-body">
        <div class="form-group">
            <label class="col-sm-3 control-label" for="form.children.dateBegin.id">Date(s)</label>
            <div class="col-sm-9">
                <div class="controls form-inline input-daterange" bs-datepicker bs-datepicker-view-date="{{ $moment().format('YYYY-MM-DD') }}">
                    <span ng-class="{'has-error': form.children.dateBegin.errors.length }">
                        <input type="text" class="form-control input-datepicker" ng-model="form.children.dateBegin.value" />
                    </span>
                    <label>au</label>
                    <span ng-class="{'has-error': form.children.dateEnd.errors.length }">
                        <input type="text" class="form-control input-datepicker" ng-model="form.children.dateEnd.value" />
                    </span>
                </div>
                <span ng-class="{'has-error': form.children.dateBegin.errors.length+form.children.dateEnd.errors }">
                    <div ng-if="error.length" ng-repeat="error in form.children.dateBegin.errors" class="help-block">{{ error }}</div>
                    <div ng-if="error.length" ng-repeat="error in form.children.dateEnd.errors" class="help-block">{{ error }}</div>
                </span>
            </div>
        </div>
        <div class="form-group" ng-class="{'has-error': form.children.scheduleMorn.errors.length }">
            <label class="col-sm-3 control-label" for="{{ form.children.scheduleMorn.id }}">{{ form.children.scheduleMorn.label }}</label>
            <div class="col-sm-3">
                <span sf-form-widget="form.children.scheduleMorn" class="form-control"/>
                <div ng-if="error.length" ng-repeat="error in form.children.scheduleMorn.errors" class="help-block">{{ error }}</div>
            </div>
        </div>
        <div class="form-group" ng-class="{'has-error': form.children.hourNumberMorn.errors.length }">
            <label class="col-sm-3 control-label" for="{{ form.children.hourNumberMorn.id }}">{{ form.children.hourNumberMorn.label }}</label>
            <div class="col-sm-3">
                <span sf-form-widget="form.children.hourNumberMorn" class="form-control"/>
                <div ng-if="error.length" ng-repeat="error in form.children.hourNumberMorn.errors" class="help-block">{{ error }}</div>
            </div>
        </div>
        <div class="form-group" ng-class="{'has-error': form.children.scheduleAfter.errors.length }">
            <label class="col-sm-3 control-label" for="{{ form.children.scheduleAfter.id }}">{{ form.children.scheduleAfter.label }}</label>
            <div class="col-sm-3">
                <span sf-form-widget="form.children.scheduleAfter" class="form-control"/>
                <div ng-if="error.length" ng-repeat="error in form.children.scheduleAfter.errors" class="help-block">{{ error }}</div>
            </div>
        </div>
        <div class="form-group" ng-class="{'has-error': form.children.hourNumberAfter.errors.length }">
            <label class="col-sm-3 control-label" for="{{ form.children.hourNumberAfter.id }}">{{ form.children.hourNumberAfter.label }}</label>
            <div class="col-sm-3">
                <span sf-form-widget="form.children.hourNumberAfter" class="form-control"/>
                <div ng-if="error.length" ng-repeat="error in form.children.hourNumberAfter.errors" class="help-block">{{ error }}</div>
            </div>
        </div>
        <div class="form-group" ng-class="{'has-error': form.children.place.errors.length }">
            <label class="col-sm-3 control-label" for="{{ form.children.place.id }}">{{ form.children.place.label }}</label>
            <div class="col-sm-9">
                <span sf-form-widget="form.children.place" class="form-control"/>
                <div ng-if="error.length" ng-repeat="error in form.children.place.errors" class="help-block">{{ error }}</div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <a class="btn btn-default" ng-click="dialog.dismiss()">Annuler</a>
        <input class="btn btn-primary" type="submit" value="Ajouter" />
    </div>
</form>

<div class="modal-header">
    <button type="button" class="close" ng-click="dialog.dismiss()" aria-hidden="true">×</button>
    <h4 class="modal-title"><i class="fa fa-warning"></i> Supprimer une date</h4>
</div>
<div class="modal-body center-block">
    <div class="alert alert-danger" role="alert">
        Êtes-vous certain de vouloir supprimer cette date <strong>{{ inscription.trainee.fullName }}</strong> à la session du <strong>{{ inscription.session.dateBegin|date:'dd/MM/yy' }}</strong> de la formation <strong>{{ inscription.session.training.name }}</strong>?
    </div>
</div>
<div class="modal-footer">
    <a class="btn btn-default" ng-click="dialog.dismiss()">Annuler</a>
    <a class="btn btn-primary" ng-click="ok()">Valider</a>
</div>

<div class="modal-header">
    <button type="button" class="close" ng-click="dialog.dismiss()" aria-hidden="true">×</button>
    <h4 class="modal-title"><i class="fa fa-graduation-cap"></i> Modifier une date</h4>
</div>

<form sf-href="dates.edit({dates: dialog.params.dates.id})" sf-form="form" json-path="form" on-success="onSuccess(data)" class="form-horizontal" novalidate>
    <div class="modal-body">
        <div class="form-group">
            <label class="col-sm-3 control-label" for="form.children.dateBegin.id">Date(s)</label>
            <div class="col-sm-9">
                <div class="controls form-inline input-daterange" bs-datepicker bs-datepicker-view-date="{{ $moment().format('YYYY-MM-DD') }}" >
                    <span ng-class="{'has-error': form.children.dateBegin.errors.length }">
                        <input type="text" class="form-control input-datepicker" ng-model="form.children.dateBegin.value" />
                    </span>
                    <label>au</label>
                    <span ng-class="{'has-error': form.children.dateEnd.errors.length }">
                        <input type="text" class="form-control input-datepicker" ng-model="form.children.dateEnd.value" />
                    </span>
                </div>
                <span ng-class="{'has-error': form.children.dateBegin.errors.length+form.children.dateEnd.errors }">
                    <div ng-if="error.length" ng-repeat="error in form.children.dateBegin.errors" class="help-block">{{ error }}</div>
                    <div ng-if="error.length" ng-repeat="error in form.children.dateEnd.errors" class="help-block">{{ error }}</div>
                </span>
            </div>
        </div>
        <div class="form-group" ng-class="{'has-error': form.children.scheduleMorn.errors.length }">
            <label class="col-sm-3 control-label" for="{{ form.children.scheduleMorn.id }}">{{ form.children.scheduleMorn.label }}</label>
            <div class="col-sm-3">
                <span sf-form-widget="form.children.scheduleMorn" class="form-control"/>
                <div ng-if="error.length" ng-repeat="error in form.children.scheduleMorn.errors" class="help-block">{{ error }}</div>
            </div>
        </div>
        <div class="form-group" ng-class="{'has-error': form.children.hourNumberMorn.errors.length }">
            <label class="col-sm-3 control-label" for="{{ form.children.hourNumberMorn.id }}">{{ form.children.hourNumberMorn.label }}</label>
            <div class="col-sm-3">
                <span sf-form-widget="form.children.hourNumberMorn" class="form-control"/>
                <div ng-if="error.length" ng-repeat="error in form.children.hourNumberMorn.errors" class="help-block">{{ error }}</div>
            </div>
        </div>
        <div class="form-group" ng-class="{'has-error': form.children.scheduleAfter.errors.length }">
            <label class="col-sm-3 control-label" for="{{ form.children.scheduleAfter.id }}">{{ form.children.scheduleAfter.label }}</label>
            <div class="col-sm-3">
                <span sf-form-widget="form.children.scheduleAfter" class="form-control"/>
                <div ng-if="error.length" ng-repeat="error in form.children.scheduleAfter.errors" class="help-block">{{ error }}</div>
            </div>
        </div>
        <div class="form-group" ng-class="{'has-error': form.children.hourNumberAfter.errors.length }">
            <label class="col-sm-3 control-label" for="{{ form.children.hourNumberAfter.id }}">{{ form.children.hourNumberAfter.label }}</label>
            <div class="col-sm-3">
                <span sf-form-widget="form.children.hourNumberAfter" class="form-control"/>
                <div ng-if="error.length" ng-repeat="error in form.children.hourNumberAfter.errors" class="help-block">{{ error }}</div>
            </div>
        </div>
        <div class="form-group" ng-class="{'has-error': form.children.place.errors.length }">
            <label class="col-sm-3 control-label" for="{{ form.children.place.id }}">{{ form.children.place.label }}</label>
            <div class="col-sm-9">
                <span sf-form-widget="form.children.place" class="form-control"/>
                <div ng-if="error.length" ng-repeat="error in form.children.place.errors" class="help-block">{{ error }}</div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <a class="btn btn-default" ng-click="dialog.dismiss()">Annuler</a>
        <input class="btn btn-primary" type="submit" value="Enregistrer" />
    </div>
</form>

<div class="modal-header">
    <button type="button" class="close" ng-click="dialog.dismiss()" aria-hidden="true">×</button>
    <h4 class="modal-title" id="myModalLabel"><i class="fa fa-warning"></i> Retirer une date d'une session</h4>
</div>
<div class="modal-body center-block">
    <div class="alert alert-danger" role="alert">
        Êtes-vous certain de vouloir retirer la date<strong>{{ dialog.params.dates.dateBegin }}</strong> de la session du <strong>{{ dialog.params.session.dateBegin | date: 'dd/MM/yyyy' }}</strong> de l'événement <strong>{{ dialog.params.session.training.name }}</strong> ?
    </div>
</div>
<div class="modal-footer">
    <a class="btn btn-default" ng-click="dialog.dismiss()">Annuler</a>
    <a class="btn btn-primary" ng-click="ok()">Valider</a>
</div>

<div class="modal-header">
    <button type="button" class="close" ng-click="dialog.dismiss()" aria-hidden="true">×</button>
    <h4 class="modal-title">
        <i class="fa fa-user"></i> {{ participation.trainer.fullName }}
        <a ui-sref="trainer.detail.view({session: participation.session.id, id: participation.trainer.id})" ng-click="dialog.dismiss()">
            <i class="fa fa-external-link"></i>
        </a>
    </h4>
</div>

<form sf-href="participation.edit({id: dialog.params.participation.id})" sf-form="form" json-path="form" on-success="onSuccess(data)" class="form-horizontal" novalidate>
    <div class="modal-body">
        <div ng-repeat="key in ['fields']">
            <div class="form-group" ng-if="form.children[key]" ng-class="{'has-error': form.children[key].errors.length }">
                <label class="col-sm-3 control-label" for="{{ form.children[key].id }}">{{ form.children[key].label }}</label>
                <div class="col-sm-9">
                    <span sf-form-widget="form.children[key]" class="form-control"/>
                    <div ng-if="error.length" ng-if="error.length" ng-repeat="error in form.children[key].errors" class="help-block">{{ error }}</div>
                </div>
            </div>
        </div>
        <div ng-if="error.length" ng-repeat="error in form.children.session.errors" class="help-block">{{ error }}</div>
        <div ng-if="error.length" ng-repeat="error in form.children.trainer.errors" class="help-block">{{ error }}</div>
        <div ng-if="error.length" ng-repeat="error in form.trainer.errors" class="help-block">{{ error }}</div>
    </div>
    <div class="modal-footer">
        <a class="btn btn-default" ng-click="dialog.dismiss()">Annuler</a>
        <input class="btn btn-primary" type="submit" value="Enregistrer" />
    </div>
</form>

<div class="modal-header">
    <button type="button" class="close" ng-click="dialog.dismiss()" aria-hidden="true">×</button>
    <h4 class="modal-title"><i class="fa fa-user"></i> Associer un formateur à une session</h4>
</div>

<form sf-href="participation.add({session: dialog.params.session.id})" sf-form="form" json-path="form" on-success="onSuccess(data)" class="form-horizontal" novalidate>
    <div class="modal-body">
        <div class="form-group" ng-class="{'has-error': form.children.trainer.errors.length || form.trainer.errors.length }">
            <label class="col-sm-3 control-label" for="{{ form.children.trainer.id }}">{{ form.children.trainer.label }}</label>
            <div class="col-sm-9">
                <input placeholder="Cliquez pour rechercher un formateur..." type="text" typeahead-template-url="mycompanybundle/trainer/dialogs/typeahead-trainer.html" typeahead-wait-ms="200" typeahead="choice as choice.label for choice in getTrainerList($viewValue)" typeahead-editable="false" typeahead-on-select="setTrainer($item)" class="form-control" ng-model="$parent.selectedTrainer" />
                <div ng-if="error.length" ng-repeat="error in form.children.session.errors" class="help-block">{{ error }}</div>
                <div ng-if="error.length" ng-repeat="error in form.children.trainer.errors" class="help-block">{{ error }}</div>
                <div ng-if="error.length" ng-repeat="error in form.trainer.errors" class="help-block">{{ error }}</div>
            </div>
        </div>
        <div ng-repeat="key in ['fields']">
            <div class="form-group" ng-if="form.children[key]" ng-class="{'has-error': form.children[key].errors.length }">
                <label class="col-sm-3 control-label" for="{{ form.children[key].id }}">{{ form.children[key].label }}</label>
                <div class="col-sm-9">
                    <span sf-form-widget="form.children[key]" class="form-control"/>
                    <div ng-if="error.length" ng-if="error.length" ng-repeat="error in form.children[key].errors" class="help-block">{{ error }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <a class="btn btn-default" ng-click="dialog.dismiss()">Annuler</a>
        <input class="btn btn-primary" type="submit" value="Ajouter" />
    </div>
</form>

<div class="modal-header">
    <button type="button" class="close" ng-click="dialog.dismiss()" aria-hidden="true">×</button>
    <h4 class="modal-title" id="myModalLabel"><i class="fa fa-warning"></i> Retirer un formateur d'une session</h4>
</div>
<div class="modal-body center-block">
    <div class="alert alert-danger" role="alert">
        Êtes-vous certain de vouloir retirer le formateur <strong>{{ dialog.params.trainer.fullName }}</strong> de la session du <strong>{{ dialog.params.session.dateBegin | date: 'dd/MM/yyyy' }}</strong> de l'événement <strong>{{ dialog.params.session.training.name }}</strong> ?
    </div>
</div>
<div class="modal-footer">
    <a class="btn btn-default" ng-click="dialog.dismiss()">Annuler</a>
    <a class="btn btn-primary" ng-click="ok()">Valider</a>
</div>

<div class="modal-header">
    <button type="button" class="close" ng-click="cancel()">×</button>
    <h4 class="modal-title">Modification de l'état des inscriptions</h4>
</div>
<div class="modal-body">
    <form novalidate class="form-horizontal" role="form">
        <div class="form-group">
            <label class="col-sm-3 control-label">Nombre de sessions </label>
            <div class="col-sm-9">
                <span class="form-control">{{ items.length }}</span>
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-3 control-label">État des inscription</label>
            <div class="col-sm-9">
                <select class="form-control" ng-model="registration" ng-options="opt.id as opt.label for opt in registrationOpts"></select>
            </div>
        </div>
    </form>
</div>
<div class="modal-footer">
    <a class="btn btn-default" ng-click="cancel()">Annuler</a>
    <a class="btn btn-primary" ng-click="ok()">Valider</a>
</div>

<div class="modal-header">
    <button type="button" class="close" ng-click="cancel()">×</button>
    <h4 class="modal-title" ng-if="inscriptionStatus">Modification du statut d'inscription</h4>
    <h4 class="modal-title" ng-if="presenceStatus">Modification du statut de présence</h4>
</div>
<div class="modal-body">
    <form novalidate class="form-horizontal" role="form">
        <div class="form-group">
            <label class="col-sm-3 control-label">Nombre de stagiaires </label>
            <div class="col-sm-9">
                <span class="form-control">{{ items.length }}</span>
            </div>
        </div>
        <div class="form-group" ng-show="inscriptionStatus">
            <label class="col-sm-3 control-label">Nouveau statut</label>
            <div class="col-sm-9">
                <span class="form-control">{{ inscriptionStatus.name }}</span>
            </div>
        </div>
        <div class="form-group" ng-show="presenceStatus">
            <label class="col-sm-3 control-label">Nouveau statut</label>
            <div class="col-sm-9">
                <span class="form-control">{{ presenceStatus.name }}</span>
            </div>
        </div>
        <div class="form-group">
            <div class="col-sm-3">
            </div>
            <div class="col-sm-9">
                <input type="checkbox" ng-model="send.Mail" id="send_mail"/>
                <label class="control-label" for="send_mail">Envoyer un email</label>
            </div>
        </div>
        <div class="form-group" ng-show='send.Mail' >
            <label for="subject" class="col-sm-3 control-label">Modèle </label>
            <div class="col-sm-9">
                <select type="text" ng-disabled='!send.Mail' class="form-control" id="template" ng-options="template.label for template in templates" ng-model="message.template" placeholder="Modèle du mail">
                </select>
            </div>
        </div>
        <div class="form-group animate-show" ng-show='send.Mail'>
            <label for="subject" class="col-sm-3 control-label">Sujet </label>
            <div class="col-sm-9">
                <input type="text" ng-disabled='!send.Mail' class="form-control" id="subject" ng-model="message.subject" placeholder="Sujet du mail">
            </div>
        </div>
        <div class="form-group" ng-show='send.Mail' >
            <label for="message" class="col-sm-3 control-label">Message </label>
            <div class="col-sm-9">
                <textarea class="form-control" ng-disabled='!send.Mail' rows="10" id="message" ng-model="message.body"  placeholder="Message du mail"></textarea>
            </div>
        </div>
        <div class="form-group" ng-show='send.Mail'>
            <div class="col-sm-3">
            </div>
            <div class="col-sm-9">
            <button type="button" ng-disabled='!send.Mail' class="btn" ng-click="preview()">Prévisualiser</button>
            </div>
        </div>
        <div class="form-group" ng-show='send.Mail && attCheckList.length'>
            <div class="col-sm-3 control-label"><b>Pièces jointes</b> </div>
            <div class="col-sm-5">
                <div ng-repeat="attachmentTemplate in attCheckList">
                <input type="checkbox" ng-model="attachmentTemplate.selected" id="attachment_{{ attachmentTemplate.id }}"/>
                <label class="control-label" for="attachment_{{ attachmentTemplate.id }}">{{ attachmentTemplate.name }}</label><a href="" class="pull-right" ng-click="previewAttachment(attachmentTemplate)"><i class="fa fa-download"></i> Prévisualiser</a>
                </div>
            </div>
        </div>

        <div class="alert alert-danger" ng-show="formError != ''">{{ formError }}</div>
    </form>
</div>
<div class="modal-footer">
    <a class="btn btn-default" ng-click="cancel()">Annuler</a>
    <a class="btn btn-primary" ng-click="ok()">Valider</a>
</div>

<div ng-repeat="key in ['organization', 'name', 'theme', 'category']">
    <div class="form-group" ng-if="form.children[key]" ng-class="{'has-error': form.children[key].errors.length }">
        <label class="col-sm-3 control-label" for="{{ form.children[key].id }}">{{ form.children[key].label }}</label>
        <div class="col-sm-9">
            <span sf-form-widget="form.children[key]" class="form-control"/>
            <div ng-if="error.length" ng-repeat="error in form.children[key].errors" class="help-block">{{ error }}</div>
        </div>
    </div>
</div>

<!-- Year - Semestre -->
<div class="form-group" ng-if="form.children.firstSessionPeriodSemester" ng-class="{'has-error': form.children.firstSessionPeriodSemester.errors.length || form.children.firstSessionPeriodYear.errors.length }">
    <label class="col-sm-3 control-label" for="{{ form.children.firstSessionPeriodSemester.id }}">{{ form.children.firstSessionPeriodSemester.label }}</label>
    <div class="col-sm-9">
        <div class="row">
            <div class="col-sm-7"><span sf-form-widget="form.children.firstSessionPeriodSemester" class="form-control"/></div>
            <div class="col-sm-5"><span sf-form-widget="form.children.firstSessionPeriodYear" class="form-control"/></div>
        </div>
        <div ng-if="error.length"  ng-repeat="error in form.children.firstSessionPeriodSemester.errors" class="help-block">{{ error }}</div>
        <div ng-if="error.length"  ng-repeat="error in form.children.firstSessionPeriodYear.errors" class="help-block">{{ error }}</div>
    </div>
</div>
<div ng-repeat="key in ['organization', 'name', 'theme', 'category']">
    <div class="form-group" ng-if="form.children[key]" ng-class="{'has-error': form.children[key].errors.length }">
        <label class="col-sm-3 control-label" for="{{ form.children[key].id }}">{{ form.children[key].label }}</label>
        <div class="col-sm-9">
            <span sf-form-widget="form.children[key]" class="form-control"/>
            <div ng-if="error.length" ng-repeat="error in form.children[key].errors" class="help-block">{{ error }}</div>
        </div>
    </div>
</div>

<!-- Year - Semestre -->
<div class="form-group" ng-if="form.children.firstSessionPeriodSemester" ng-class="{'has-error': form.children.firstSessionPeriodSemester.errors.length || form.children.firstSessionPeriodYear.errors.length }">
    <label class="col-sm-3 control-label" for="{{ form.children.firstSessionPeriodSemester.id }}">{{ form.children.firstSessionPeriodSemester.label }}</label>
    <div class="col-sm-9">
        <div class="row">
            <div class="col-sm-7"><span sf-form-widget="form.children.firstSessionPeriodSemester" class="form-control"/></div>
            <div class="col-sm-5"><span sf-form-widget="form.children.firstSessionPeriodYear" class="form-control"/></div>
        </div>
        <div ng-if="error.length"  ng-repeat="error in form.children.firstSessionPeriodSemester.errors" class="help-block">{{ error }}</div>
        <div ng-if="error.length"  ng-repeat="error in form.children.firstSessionPeriodYear.errors" class="help-block">{{ error }}</div>
    </div>
</div>
<div ng-repeat="key in ['organization', 'name', 'category', 'theme']">
    <div class="form-group" ng-if="form.children[key]" ng-class="{'has-error': form.children[key].errors.length }">
        <label class="col-sm-3 control-label" for="{{ form.children[key].id }}">{{ form.children[key].label }}</label>
        <div class="col-sm-9">
            <span sf-form-widget="form.children[key]" class="form-control"/>
            <div ng-if="error.length" ng-repeat="error in form.children[key].errors" class="help-block">{{ error }}</div>
        </div>
    </div>
</div>

<!-- Session -->
<!--<div ng-repeat="key in ['name']">-->
    <!--<div class="form-group" ng-if="form.children.session.children[key]" ng-class="{'has-error': form.children.session.children[key].errors.length }">-->
        <!--<label class="col-sm-3 control-label" for="{{ form.children.session.children[key].id }}">{{ form.children.session.children[key].label }} </label>-->
        <!--<div class="col-sm-9">-->
            <!--<span sf-form-widget="form.children.session.children[key]" class="form-control"/>-->
            <!--<div ng-if="error.length" ng-repeat="error in form.children.session.children[key].errors" class="help-block">{{ error }}</div>-->
        <!--</div>-->
    <!--</div>-->
<!--</div>-->

<div class="form-group">
    <label class="col-sm-3 control-label" for="form.children.session.children.dateBegin.id">Date(s)</label>
    <div class="col-sm-9">
        <div class="controls form-inline input-daterange" bs-datepicker>
            <span ng-class="{'has-error': form.children.session.children['dateBegin'].errors.length }">
                <input type="text" class="form-control input-datepicker" ng-model="form.children.session.children['dateBegin'].value" />
            </span>
            <label>au</label>
            <span ng-class="{'has-error': form.children.session.children['dateEnd'].errors.length }">
                <input type="text" class="form-control input-datepicker" ng-model="form.children.session.children['dateEnd'].value" />
            </span>
        </div>
        <span ng-class="{'has-error': form.children.session.children['dateBegin'].errors.length+form.children.session.children['dateEnd'].errors }">
            <div ng-if="error.length" ng-repeat="error in form.children.session.children['dateBegin'].errors" class="help-block">{{ error }}</div>
            <div ng-if="error.length" ng-repeat="error in form.children.session.children.['dateEnd'].errors" class="help-block">{{ error }}</div>
        </span>
    </div>
</div>

<div class="form-group" ng-if="form.children.session.children['maximumNumberOfRegistrations']" ng-class="{'has-error': form.children.session.children['maximumNumberOfRegistrations'].errors.length }">
    <label class="col-sm-3 control-label" for="{{ form.children.session.children['maximumNumberOfRegistrations'].id }}">{{ form.children.session.children['maximumNumberOfRegistrations'].label }}</label>
    <div class="col-sm-3">
        <span sf-form-widget="form.children.session.children['maximumNumberOfRegistrations']" class="form-control"/>
        <div ng-if="error.length" ng-repeat="error in form.children.session.children['maximumNumberOfRegistrations'].errors" class="help-block">{{ error }}</div>
    </div>
</div>

<div ng-repeat="key in ['dayNumber', 'hourNumber']">
    <div class="form-group" ng-if="form.children.session.children[key]" ng-class="{'has-error': form.children.session.children[key].errors.length }">
        <label class="col-sm-3 control-label" for="{{ form.children.session.children[key].id }}">{{ form.children.session.children[key].label }}</label>
        <div class="col-sm-9">
            <span sf-form-widget="form.children.session.children[key]" class="form-control"/>
            <div ng-if="error.length" ng-if="error.length" ng-repeat="error in form.children.session.children[key].errors" class="help-block">{{ error }}</div>
        </div>
    </div>
</div>
<a ui-sref="session.detail.view({id: result.id})" title="{{ result.training.name }}">
    <div class="list-group-item-title">{{ result.training.name }}</div>
    <div class="list-group-item-text">
        {{ result.dateBegin | date: 'dd/MM/yyyy' }}
    </div>
</a>

<form sf-xeditable-form="form" sf-href='session.view({id: session.id})' on-success="onSuccess(data)">
    <div class="row session">

        <!--
         Infos
        -->
        <div class="col-md-8">
            <div class="btn-group pull-right">
                <a class="btn btn-fa" href="" tooltip="Publipostage" ng-click="$dialog.open('batch.publipost', { items: [session.id], service: 'session' })"><span class="fa fa-file-word-o"></span></a>
                <a class="btn btn-fa" href="" tooltip="Voir l'événement" ng-if="session.training._accessRights.view" ui-sref="training.detail.view({id: session.training.id})"><span class="fa fa-calendar"></span></a>
                <a class="btn btn-fa" href="" ng-if="!session.promote" tooltip="Promouvoir la session" ng-if="session._accessRights.edit" ng-click="promote(1)"><span class="fa fa-star-o"></span></a>
                <a class="btn btn-fa" href="" ng-if="session.promote" tooltip="Dépromouvoir la session" ng-if="session._accessRights.edit" ng-click="promote(0)"><span class="fa fa-star fa-highlight"></span></a>
                <a class="btn btn-fa" href="" tooltip="Dupliquer" ng-click="duplicate()"><span class="fa fa-copy"></span></a>
                <a class="btn btn-fa" href="" tooltip="Supprimer" ng-click="delete()"><span class="fa fa-trash-o"></span></a>
            </div>

            <div class="pre-title">{{ session.training.typeLabel }} n°{{ session.training.id }} -  {{ session.training.name }}</div>
            <h2>Session <span sf-xeditable="form.children.name" data-type="text">{{ session.name }}</span> du {{ session.dateBegin|date: 'dd MMMM y' }} <span ng-if="session.dateEnd"> au {{ session.dateEnd|date: 'dd MMMM y' }}</span></h2>

            <div class="infos" ng-if="form.children.module">
                <div><label>Module :</label> <span sf-xeditable="form.children.module" data-type="select">{{ session.module.name }}</span></div>
                <div><label>Type :</label> <span sf-xeditable="form.children.type" data-type="select">{{ session.type.name }}</span></div>
            </div>

            <h3>Informations</h3>
            <hr>
            <div class="row mb-1">
                <div class="col-lg-6">
                    <ul class="summary">
                        <li><label>Inscriptions</label> <span sf-xeditable="form.children.registration">{{ $trainingBundle.registrationStates[session.registration] }}</span></li>
                        <li><label>Afficher en ligne</label> <span sf-xeditable="form.children.displayOnline">{{ session.displayOnline ? 'Oui' : 'Non' }}</span></li>
                        <li><label>Statut</label> <span sf-xeditable="form.children.status">{{ $trainingBundle.statusStates[session.status] }}</span></li>
                        <li><label>Date</label> <span>{{ session.dateBegin|date: 'dd/MM/yyyy' }}</span></li>
                        <li><label>Date de fin</label> <span>{{ session.dateEnd|date: 'dd/MM/yyyy' }}</span></li>
                        <li><label>Programmation</label> <span sf-xeditable="form.children.sessionType">{{ session.sessionType.name }}</span></li>
                        <li ng-if="session.sessionType.name == 'A venir'"><a class="btn btn-xs btn-default" href="" ng-click="sendAlerts()"><span class="fa fa-envelope"></span> Envoyer les alertes d'ouverture de session</a></li>
                    </ul>
                </div>
                <div class="col-lg-6">
                    <ul class="summary">
                        <li ng-if="!session.registration"><label>Nombre d'inscriptions</label> <span sf-xeditable="form.children.numberOfRegistrations">{{ session.numberOfRegistrations }}</span></li>
                        <li><label>Participants max</label> <span sf-xeditable="form.children.maximumNumberOfRegistrations">{{ session.maximumNumberOfRegistrations }}</span></li>
                        <li><label>Date limite</label> <span sf-xeditable="form.children.limitRegistrationDate">{{ session.limitRegistrationDate|date: 'dd/MM/yyyy' }}</span></li>
                        <li><label>Tarif</label> <span sf-xeditable="form.children.price">{{ session.price ? session.price : '0' }} €</span></li>
                        <li><label>Nombre d'heures</label> <span>{{ session.hourNumber }}</span></li>
                        <li><label>Nombre de jours</label> <span>{{ session.dayNumber }}</span></li>
                    </ul>
                </div>
            </div>

            <h3>Dates</h3>
            <hr>
            <!--
             Dates
            -->
            <!--div dates-block="session"></div-->
            <div ng-include src="'mycompanybundle/training/session/states/detail/partials/dates.html'" ng-controller="DatesViewController"></div>

            <ul class="nav nav-tabs">
                <li ng-click="tab = 'inscriptions'" class="active"><a href="" data-toggle="tab"><span class="fa fa-graduation-cap"></span> {{ session.registration > 0 ? 'Inscriptions (' + session.inscriptions.length + ')' : 'Participants (' + getTotal() + ')' }}</a></li>
                <li ng-click="tab = 'alerts'"><a href="" data-toggle="tab"><span class="fa fa-bell"></span> Inscrits aux alertes ({{ session.alerts.length ? session.alerts.length : 0 }})</a></li>
                <li ng-click="tab = 'messages'"><a href="" data-toggle="tab"><span class="fa fa-send"></span> Messages ({{ session.messages.length ? session.messages.length : 0 }})</a></li>
                <li ng-click="tab = 'evals'"><a href="" data-toggle="tab"><span class="fa fa-smile-o"></span> Evaluations </a></li>
            </ul>

            <!--
             Inscriptions
            -->
            <div ng-show="!tab || tab === 'inscriptions'">
                <div class="row">
                    <div class="col-lg-12">
                        <!-- opened inscriptions -->
                        <div ng-if="session.registration > 0" ng-include src="'mycompanybundle/training/session/states/detail/partials/inscriptions.html'" ng-controller="SessionInscriptionsController"></div>
                        <!-- closed inscriptions -->
                        <div ng-if="session.registration == 0" ng-include src="'mycompanybundle/training/session/states/detail/partials/participants-summary.html'" ng-controller="SessionParticipantsSummaryController"></div>
                    </div>
                </div>
            </div>

            <!--
             Emails
            -->
            <div ng-show="tab === 'messages'">
                <div class="row mb-1">
                    <div class="col-lg-12">
                        <div entity-emails session="session.id"></div>
                    </div>
                </div>
            </div>

            <!--
             Evaluations
            -->
            <div ng-show="tab === 'evals'">
                <div class="row mb-1">
                    <div class="col-lg-12">
                        <div ng-include src="'mycompanybundle/training/session/states/detail/partials/evals.html'" ng-controller="EvalComputeController"></div>
                    </div>
                </div>
            </div>

            <!--
             Alertes
            -->
            <div ng-show="tab === 'alerts'">
                <div class="row mb-1">
                    <div class="col-lg-12">
                        <div>
                            <div ng-if="!session.alerts.length" class="well well-empty well-sm">
                                Il n'y a aucune inscription à l'alerte d'ouverture de cette session.
                            </div>

                            <table ng-if="session.alerts.length" class="table table-hover table-search table-condensed">
                                <tbody>
                                <tr ng-repeat="alert in session.alerts | filter:filter | orderBy:'createdAt':true">
                                    <td> {{ alert.createdAt | date: 'dd/MM/yyyy' }} </td>
                                    <td> <a title="Voir le profil du stagiaire" href="" ui-sref="trainee.detail.view({ id: alert.trainee.id })">{{ alert.trainee.fullName }}</a> </td>
                                </tr>
                                </tbody>
                            </table>

                        </div>
                    </div>
                </div>
            </div>

        </div>


        <!--
         Sidebar
        -->
        <div class="col-md-4 sidebar">

            <!--
             Url
            -->
            <div class="block">
                <div class="block-body">
                    <div input-copy-clipboard="session.frontUrl"></div>
                </div>
            </div>

            <!--
             Trainers
            -->
            <div trainers-block="session"></div>

            <!--
             MATERIALS
            -->
            <div materials-block="session" entity-type="'session'"></div>

            <!--
             Coûts
             -->
            <div class="block block-costs">
                <div class="block-title">
                    <span class="pull-right">{{ session.totalCost | currency }}</span>
                    <span class="fa fa-euro"></span> Coûts
                </div>
                <div class="block-body">
                    <ul class="list-unstyled text-small text-gray-light">
                        <li><label>Coûts pédagogiques</label> <span class="pull-right inline-block" sf-xeditable="form.children.teachingCost" data-mode="popup" data-placement="left">{{ session.teachingCost | currency }}</span></li>
                        <li><label>Coûts en vacation</label> <span class="pull-right inline-block" sf-xeditable="form.children.vacationCost" data-mode="popup" data-placement="left">{{ session.vacationCost | currency }}</span></li>
                        <li><label>Frais de mission : hébergement</label> <span class="pull-right inline-block" sf-xeditable="form.children.accommodationCost" data-mode="popup" data-placement="left">{{ session.accommodationCost | currency }}</span></li>
                        <li><label>Frais de mission : repas</label> <span class="pull-right inline-block" sf-xeditable="form.children.mealCost" data-mode="popup" data-placement="left">{{ session.mealCost | currency }}</span></li>
                        <li><label>Frais de mission : transports</label> <span class="pull-right inline-block" sf-xeditable="form.children.transportCost" data-mode="popup" data-placement="left">{{ session.transportCost | currency }}</span></li>
                        <li><label>Frais de supports</label> <span class="pull-right inline-block" sf-xeditable="form.children.materialCost" data-mode="popup" data-placement="left">{{ session.materialCost | currency }}</span></li>
                    </ul>
                </div>
            </div>

            <!--
             Recettes
             -->
            <div class="block block-costs">
                <div class="block-title">
                    <span class="pull-right">{{ session.totalTaking | currency }}</span>
                    <span class="fa fa-euro"></span> Recettes
                </div>
                <div class="block-body">
                    <ul class="list-unstyled text-small text-gray-light">
                        <li><label>Recettes</label> <span class="pull-right inline-block" sf-xeditable="form.children.taking" data-mode="popup" data-placement="left">{{ session.taking | currency }}</span></li>
                    </ul>
                </div>
            </div>

            <!--
             Comments
            -->
            <div class="block block-comments">
                <div class="block-title">
                    <span class="fa fa-comment-o"></span> Commentaires
                </div>
                <div class="block-body">
                    <span sf-xeditable="form.children.comments" data-type="textarea">{{ session.comments }}</span>
                </div>
            </div>
        </div>
    </div>

</form>

<div class="modal-header">
    <button type="button" class="close" ng-click="dialog.dismiss()" aria-hidden="true">×</button>
    <h4 class="modal-title"><i class="fa fa-copy"></i> Dupliquer une formation</h4>
</div>
<form sf-href="training.choosetypeduplicate()" sf-form="form" json-path="form" on-success="onSuccess(data)" class="form-horizontal" novalidate>
    <div class="modal-body center-block">
        <div ng-repeat="key in ['duplicatedType']">
            <div class="form-group" ng-if="form.children[key]" ng-class="{'has-error': form.children[key].errors.length }">
                <label class="col-sm-3 control-label" for="{{ form.children[key].id }}">{{ form.children[key].label }}</label>
                <div class="col-sm-9">
                    <span sf-form-widget="form.children[key]" class="form-control"/>
                    <div ng-if="error.length" ng-repeat="error in form.children[key].errors" class="help-block">{{ error }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <a class="btn btn-default" ng-click="dialog.dismiss()">Annuler</a>
        <input class="btn btn-primary" type="submit" value="Valider" />
    </div>
</form>

<div class="modal-header">
    <button type="button" class="close" ng-click="dialog.dismiss()" aria-hidden="true">×</button>
    <h4 class="modal-title" id="myModalLabel">Dupliquer : {{ form.value.typeLabel }}</h4>
</div>
<form sf-href="training.duplicate({id: training.id, type: type})" sf-form="form" json-path="form" on-success="onSuccess(data)" class="form-horizontal" novalidate>
    <div class="modal-body">
        <ng-include src="'mycompanybundle/training/training/dialogs/common/internship.html'"></ng-include>
    </div>
    <div class="modal-footer">
        <a class="btn btn-default" ng-click="dialog.dismiss()">Annuler</a>
        <input class="btn btn-primary" type="submit" value="Enregistrer" />
    </div>
</form>

<div class="modal-header">
    <button type="button" class="close" ng-click="dialog.dismiss()" aria-hidden="true">×</button>
    <h4 class="modal-title" id="myModalLabel">Dupliquer : {{ form.value.typeLabel }}</h4>
</div>
<form sf-href="training.duplicate({id: training.id, type: type})" sf-form="form" json-path="form" on-success="onSuccess(data)" class="form-horizontal" novalidate>
    <div class="modal-body">
        <ng-include src="'mycompanybundle/training/training/dialogs/common/internship.html'"></ng-include>
    </div>
    <div class="modal-footer">
        <a class="btn btn-default" ng-click="dialog.dismiss()">Annuler</a>
        <input class="btn btn-primary" type="submit" value="Enregistrer" />
    </div>
</form>

<div class="modal-header">
    <button type="button" class="close" ng-click="dialog.dismiss()" aria-hidden="true">×</button>
    <h4 class="modal-title" id="myModalLabel">Dupliquer : {{ form.value.typeLabel }}</h4>
</div>
<form sf-href="training.duplicate({id: training.id, type: type})" sf-form="form" json-path="form" on-success="onSuccess(data)" class="form-horizontal" novalidate>
    <div class="modal-body">
        <ng-include src="'mycompanybundle/training/training/dialogs/common/meeting.html'"></ng-include>
    </div>
    <div class="modal-footer">
        <a class="btn btn-default" ng-click="dialog.dismiss()">Annuler</a>
        <input class="btn btn-primary" type="submit" value="Enregistrer" />
    </div>
</form>

<div class="modal-header">
    <button type="button" class="close" ng-click="dialog.dismiss()" aria-hidden="true">×</button>
    <h4 class="modal-title" id="myModalLabel">Créer : {{ trainingType.label }}</h4>
</div>
<form sf-href="training.create({type: dialog.params.type})" sf-form="form" json-path="form" on-success="onSuccess(data)" class="form-horizontal" novalidate>
    <div class="modal-body">
        <ng-include src="'mycompanybundle/training/training/dialogs/common/internship.html'"></ng-include>
    </div>
    <div class="modal-footer">
        <a class="btn btn-default" ng-click="dialog.dismiss()">Annuler</a>
        <input class="btn btn-primary" type="submit" value="Enregistrer" />
    </div>
</form>

<div class="modal-header">
    <button type="button" class="close" ng-click="dialog.dismiss()" aria-hidden="true">×</button>
    <h4 class="modal-title" id="myModalLabel">Créer : {{ trainingType.label }}</h4>
</div>
<form sf-href="training.create({type: dialog.params.type})" sf-form="form" json-path="form" on-success="onSuccess(data)" class="form-horizontal" novalidate>
    <div class="modal-body">
        <ng-include src="'mycompanybundle/training/training/dialogs/common/internship.html'"></ng-include>
    </div>
    <div class="modal-footer">
        <a class="btn btn-default" ng-click="dialog.dismiss()">Annuler</a>
        <input class="btn btn-primary" type="submit" value="Enregistrer" />
    </div>
</form>

<div class="modal-header">
    <button type="button" class="close" ng-click="dialog.dismiss()" aria-hidden="true">×</button>
    <h4 class="modal-title" id="myModalLabel">Créer : {{ trainingType.label }}</h4>
</div>
<form sf-href="training.create({type: dialog.params.type})" sf-form="form" json-path="form" on-success="onSuccess(data)" class="form-horizontal" novalidate>
    <div class="modal-body">
        <ng-include src="'mycompanybundle/training/training/dialogs/common/meeting.html'"></ng-include>
    </div>
    <div class="modal-footer">
        <a class="btn btn-default" ng-click="dialog.dismiss()">Annuler</a>
        <input class="btn btn-primary" type="submit" value="Enregistrer" />
    </div>
</form>

<div class="full-height-container is-full-width is-absolute is-direction-column">

    <!-- Results -->
    <div ng-if="search.result.total" class="full-height-item is-full-width is-grow">
        <div class="col-xs-12">
            <table search-table ng-class="{loading: search.processing}">
                <thead>
                    <tr>
                        <th></th>
                        <th search-table-th field="dateBegin" class="visible">Date</th>
                        <th search-table-th field="training.name.source">Formation</th>
                        <th search-table-th field="registration" class="visible">Inscriptions</th>
                        <th search-table-th field="status">Statut</th>
                        <th search-table-th field="numberOfRegistrations">Inscrits</th>
                        <th>Statistiques</th>
                        <th search-table-th field="numberOfParticipants">Présents</th>
                        <th search-table-th field="displayOnline">En ligne</th>
                    </tr>
                </thead>
                <tbody>
                    <tr ng-repeat="item in search.result.items" ng-class="{warning: isSelected(item.id)}">
                        <td ng-click="switchSelect(item.id)" stop-event><i class="fa" ng-class="{'fa-square-o': !isSelected(item.id), 'fa-check-square-o': isSelected(item.id)}"></i></td>
                        <td><a href="" ui-sref="session.detail.view({id: item.id})">{{ item.dateBegin|date: 'dd/MM/yyyy' }}</a></td>
                        <td><a href="" ui-sref="training.detail.view({id: item.training.id + '_' + item.year + '_' + item.semester})">{{ item.training.name }}</a></td>
                        <td>
                            <span registration-label="item" large></span>
                        </td>
                        <td>{{ $trainingBundle.statusStates[item.status] }}</td>
                        <td>{{ item.numberOfRegistrations }}</td>
                        <td>
                            <a registration-stats-label="item" class="label-lg"></a>
                        </td>
                        <td>{{ item.numberOfParticipants }}</td>
                        <td><span class="label label-lg" ng-class="item.displayOnline ? 'label-success' : 'label-default'">{{ item.displayOnline ? "Oui" : "Non" }}</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div ng-if="search.result.total" class="full-height-item is-full-width">
        <div search-table-controls></div>
    </div>

    <!-- No results -->
    <div class="full-height-item is-full-width is-grow" ng-if="search.executed && !search.result.total">
        <div class="col-xs-12">
            <h1>Oops!</h1>
            <p>Il n'y a aucune session correspondante à votre recherche.</p>
        </div>
    </div>

</div>

<div class="modal-header">
    <button type="button" class="close" ng-click="dialog.dismiss()" aria-hidden="true">×</button>
    <h4 class="modal-title" id="myModalLabel">Editer {{ module.name }}</h4>
</div>
<form sf-href="module.edit({id: dialog.params.module.id})" sf-form="form" json-path="form" on-success="onSuccess(data)" class="form-horizontal" novalidate>
    <div class="modal-body">
        <div ng-repeat="key in ['name', 'mandatory']">
            <div class="form-group" ng-if="form.children[key]" ng-class="{'has-error': form.children[key].errors.length }">
                <label class="col-sm-3 control-label" for="{{ form.children[key].id }}">{{ form.children[key].label }}</label>
                <div class="col-sm-9">
                    <span sf-form-widget="form.children[key]" class="form-control"/>
                    <div ng-if="error.length" ng-repeat="error in form.children[key].errors" class="help-block">{{ error }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <a class="btn btn-default" ng-click="dialog.dismiss()">Annuler</a>
        <input class="btn btn-primary" type="submit" value="Enregistrer" />
    </div>
</form>

<div class="modal-header">
    <button type="button" class="close" ng-click="dialog.dismiss()" aria-hidden="true">×</button>
    <h4 class="modal-title"><i class="fa fa-warning"></i> Supprimer une formation</h4>
</div>
<div class="modal-body center-block">
    <div class="alert alert-danger" role="alert">
        Êtes-vous certain de vouloir supprimer la formation <strong>{{ dialog.params.training.name }}</strong> ? <br/>
        Attention cette action supprimera les sessions et inscriptions de cette formation !
    </div>
</div>
<div class="modal-footer">
    <a class="btn btn-default" ng-click="dialog.dismiss()">Annuler</a>
    <a class="btn btn-primary" ng-click="ok()">Valider</a>
</div>

<div ng-if="training" class="row">
    <div class="col-md-8">
        <form sf-xeditable-form="form" sf-href='training.view({id: training.id})' on-success="onSuccess(data)">

            <!-- training form -->
            <ng-include src="'mycompanybundle/training/training/states/detail/partials/training.form.html'"></ng-include>

            <h3>Méthodes pédagogiques</h3><hr>
            <p><span sf-xeditable="form.children.teachingMethods" data-type="textarea">{{ training.teachingMethods }}</span></p>

            <!-- training : intership -->
            <h3><span>{{ training.typeLabel }}</span></h3><hr>
            <div class="row">
                <div class="col-lg-6">
                    <ul class="summary">
                        <li><label>Type de formation</label> <span sf-xeditable="form.children.category">{{ training.category.name }}</span></li>
                        <li><label>Publics prioritaires</label> <span sf-xeditable="form.children.publicTypes" data-type="select2">{{ training.publicTypes | joinObjects:'machineName' }}</span></li>
                        <li><label>Publics cibles</label> <span sf-xeditable="form.children.publicTypesRestrict" data-type="select2">{{ training.publicTypesRestrict | joinObjects:'machineName' }}</span></li>
                        <li><label>Etablissement</label> <span sf-xeditable="form.children.institution">{{ training.institution.name }}</span></li>
                        <li><label>Prérequis</label> <span sf-xeditable="form.children.prerequisites" data-type="textarea">{{ training.prerequisites }}</span></li>
                    </ul>
                </div>
                <div class="col-lg-6">
                    <ul class="summary">
                        <li><label>Type d'intervention</label> <span sf-xeditable="form.children.interventionType">{{ training.interventionType }}</span></li>
                        <li><label>Initiative extérieure</label> <span sf-xeditable="form.children.externalInitiative">{{ training.externalInitiative ? 'Oui' : 'Non' }}</span></li>
                        <li><label>Public désigné</label> <span sf-xeditable="form.children.designatedPublic">{{ training.designatedPublic ? 'Oui' : 'Non' }}</span></li>
                        <li><label>Responsable pédag.</label> <span sf-xeditable="form.children.supervisor" data-mode="popup" data-type="select">{{ training.supervisor.fullName }}</span></li>
                        <li><label>1<sup>ère</sup> session</label>
                            <span>
                                <span sf-xeditable="form.children.firstSessionPeriodSemester">{{ training.firstSessionPeriodSemester == 1 ? '1er semestre' : (training.firstSessionPeriodSemester == 2 ? '2nd semestre' : '') }}</span>
                                <span sf-xeditable="form.children.firstSessionPeriodYear" class="inline-block">{{ training.firstSessionPeriodYear }}</span>
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </form>
    </div>

    <!--
    -- SIDEBAR
    -->
    <div class="col-md-4 sidebar">
        <ng-include src="'mycompanybundle/training/training/states/detail/partials/sessions.block.html'"></ng-include>
        <div materials-block="training" entity-type="training.type"></div>
        <ng-include src="'mycompanybundle/training/training/states/detail/partials/comments.block.html'"></ng-include>
    </div>
</div>

<div ng-if="training" class="row">
    <div class="col-md-8">
        <form sf-xeditable-form="form" sf-href='training.view({id: training.id})' on-success="onSuccess(data)">

            <!-- training form -->
            <ng-include src="'mycompanybundle/training/training/states/detail/partials/training.form.html'"></ng-include>

            <h3>Description</h3><hr>
            <p><span sf-xeditable="form.children.description" data-type="textarea">{{ training.description }}</span></p>

            <h3>Méthodes pédagogiques</h3><hr>
            <p><span sf-xeditable="form.children.teachingMethods" data-type="textarea">{{ training.teachingMethods }}</span></p>

            <!-- training : intership -->
            <h3><span>{{ training.typeLabel }}</span></h3><hr>
            <div class="row">
                <div class="col-lg-6">
                    <ul class="summary">
                        <li><label>Type de formation</label> <span sf-xeditable="form.children.category">{{ training.category.name }}</span></li>
                        <li><label>Publics prioritaires</label> <span sf-xeditable="form.children.publicTypes" data-type="select2">{{ training.publicTypes | joinObjects:'name' }}</span></li>
                        <li><label>Etablissement</label> <span sf-xeditable="form.children.institution">{{ training.institution.name }}</span></li>
                        <li><label>Prérequis</label> <span sf-xeditable="form.children.prerequisites" data-type="textarea">{{ training.prerequisites }}</span></li>
                    </ul>
                </div>
                <div class="col-lg-6">
                    <ul class="summary">
                        <li><label>Type d'intervention</label> <span sf-xeditable="form.children.interventionType">{{ training.interventionType }}</span></li>
                        <li><label>Initiative extérieure</label> <span sf-xeditable="form.children.externalInitiative">{{ training.externalInitiative ? 'Oui' : 'Non' }}</span></li>
                        <li><label>Responsable pédag.</label> <span sf-xeditable="form.children.supervisor" data-mode="popup" data-type="select">{{ training.supervisor.fullName }}</span></li>
                        <li><label>1<sup>ère</sup> session</label>
                            <span>
                                <span sf-xeditable="form.children.firstSessionPeriodSemester">{{ training.firstSessionPeriodSemester == 1 ? '1er semestre' : (training.firstSessionPeriodSemester == 2 ? '2nd semestre' : '') }}</span>
                                <span sf-xeditable="form.children.firstSessionPeriodYear" class="inline-block">{{ training.firstSessionPeriodYear }}</span>
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </form>
    </div>

    <!--
    -- SIDEBAR
    -->
    <div class="col-md-4 sidebar">
        <ng-include src="'mycompanybundle/training/training/states/detail/partials/module_sessions.block.html'"></ng-include>
        <div materials-block="training" entity-type="training.type"></div>
        <ng-include src="'mycompanybundle/training/training/states/detail/partials/comments.block.html'"></ng-include>
    </div>
</div>


<form sf-xeditable-form="form" sf-href='training.view({id: training.id})' on-success="onSuccess(data)">
    <div ng-if="training" class="row" xmlns="http://www.w3.org/1999/html">
        <div class="col-md-8">
            <div class="btn-group pull-right">
                <a class="btn btn-fa" href="" tooltip="Bilan" ng-if="training.sessions.length && training._accessRights.view" ng-click="getBalanceSheet()"><span class="fa fa-file-excel-o"></span></a>
                <a class="btn btn-fa" href="" tooltip="Publipostage" ng-click="$dialog.open('batch.publipost', { items: [training.id], service: 'training' })"><span class="fa fa-file-word-o"></span></a>
                <a class="btn btn-fa" href="" ng-if="!training.session.promote" tooltip="Promouvoir la session" ng-if="training._accessRights.edit" ng-click="promote(1)"><span class="fa fa-star-o"></span></a>
                <a class="btn btn-fa" href="" ng-if="training.session.promote" tooltip="Dépromouvoir la session" ng-if="training._accessRights.edit" ng-click="promote(0)"><span class="fa fa-star fa-highlight"></span></a>
                <a class="btn btn-fa" href="" tooltip="Dupliquer" ng-if="training._accessRights.edit" ng-click="duplicate()"><span class="fa fa-copy"></span></a>
                <a class="btn btn-fa" href="" tooltip="Supprimer" ng-if="training._accessRights.delete" ng-click="delete()"><span class="fa fa-trash-o"></span></a>
            </div>

            <div class="pre-title">{{ training.typeLabel }} n°{{ training.number }} -  {{ training.organization.name }}</div>
            <h2><span sf-xeditable="form.children.name" data-type="text">{{ training.name }}</span></h2>

            <!--
             Infos
            -->
            <div class="row">
                <div class="col-lg-6">
                    <ul class="summary">
                        <li><label>Type</label> <span sf-xeditable="form.children.category">{{ training.category.name }}</span></li>
                        <li><label>National</label> <span sf-xeditable="form.children.national">{{ training.national ? 'Oui' : 'Non' }}</span></li>
                        <li><label>Thématique</label> <span sf-xeditable="form.children.theme" data-type="select">{{ training.theme.name }}</span></li>
                        <li><label>Date</label><span sf-xeditable="form.children.session.children.dateBegin" data-placement="right">{{ training.session.dateBegin|date: 'dd/MM/yyyy' }}</span></li>
                        <li><label>Date de fin</label> <span sf-xeditable="form.children.session.children.dateEnd">{{ training.session.dateEnd|date: 'dd/MM/yyyy' }}</span></li>
                        <li><label>Horaires</label> <span sf-xeditable="form.children.session.children.schedule">{{ session.schedule }}</span></li>
                        <li><label>Nombre d'heures</label> <span sf-xeditable="form.children.session.children.hourNumber">{{ session.hourNumber }}</span></li>
                        <li><label>Nombre de jours</label> <span sf-xeditable="form.children.session.children.dayNumber">{{ session.dayNumber }}</span></li>
                        <li><label>Tarif</label> <span sf-xeditable="form.children.session.children.price">{{ session.price ? session.price : 0 }} &euro;</span></li>
                    </ul>
                </div>
                <div class="col-lg-6">
                    <ul class="summary">
                        <li><label>Lieu</label> <span sf-xeditable="form.children.session.children.place" data-mode="popup" data-type="select">{{ session.place.name }}</span></li>
                        <li><label>Inscriptions</label> <span sf-xeditable="form.children.session.children.registration">{{ $trainingBundle.registrationStates[training.session.registration] }}</span></li>
                        <li><label>Afficher en ligne</label> <span sf-xeditable="form.children.session.children.displayOnline">{{ training.session.displayOnline ? 'Oui' : 'Non' }}</span></li>
                        <li><label>Participants max</label> <span sf-xeditable="form.children.session.children.maximumNumberOfRegistrations">{{ training.session.maximumNumberOfRegistrations }}</span></li>
                        <li><label>Date limite</label> <span sf-xeditable="form.children.session.children.limitRegistrationDate">{{ training.session.limitRegistrationDate|date: 'dd/MM/yyyy' }}</span></li>
                        <li ng-if="!training.session.registration"><label>Nombre d'inscriptions</label> <span sf-xeditable="form.children.session.children.numberOfRegistrations">{{ training.session.numberOfRegistrations }}</span></li>
                    </ul>
                </div>
            </div>

            <!--
             Program
            -->
            <div class="row mg-1">
                <div class="col-lg-6">
                    <h3>Programme</h3><hr>
                    <p><span sf-xeditable="form.children.program" data-type="textarea">{{ training.program }}</span></p>

                    <ul class="nav nav-tabs">
                        <li ng-click="tab = 'inscriptions'" class="active"><a href="" data-toggle="tab"><span class="fa fa-graduation-cap"></span> Inscriptions ({{ session.inscriptions.length }})</a></li>
                        <li ng-click="tab = 'messages'"><a href="" data-toggle="tab"><span class="fa fa-send"></span> Messages ({{ session.messages.length ? session.messages.length : 0 }})</a></li>
                    </ul>
                </div>
            </div>

            <!--
             Inscriptions
            -->
            <div ng-show="!tab || tab === 'inscriptions'">
                <div class="row">
                    <div class="col-lg-12">
                        <!-- opened inscriptions -->
                        <div ng-if="session.registration > 0" ng-include src="'mycompanybundle/training/session/states/detail/partials/inscriptions.html'" ng-controller="SessionInscriptionsController"></div>
                        <!-- closed inscriptions -->
                        <div ng-if="session.registration == 0" ng-include src="'mycompanybundle/training/session/states/detail/partials/participants-summary.html'" ng-controller="SessionParticipantsSummaryController"></div>
                    </div>
                </div>
            </div>

            <!--
             Emails
            -->
            <div ng-show="tab === 'messages'">
                <div class="row mb-1">
                    <div class="col-lg-12">
                        <div entity-emails session="session.id"></div>
                    </div>
                </div>
            </div>

        </div>

        <div class="col-md-4 sidebar">

            <!--
             Url
            -->
            <div class="block" ng-if="session.displayOnline === true && session.registration !== 2">
                <div class="block-body">
                    <div input-copy-clipboard="session.publicUrl"></div>
                </div>
            </div>

            <div class="block" ng-if="session.registration === 2">
                <div class="block-body">
                    <div input-copy-clipboard="session.privateUrl"></div>
                </div>
            </div>

            <!--
             Comments
            -->
            <div class="block block-comments">
                <div class="block-title">
                    <span class="fa fa-comment-o"></span> Commentaires
                </div>
                <div class="block-body">
                    <span sf-xeditable="form.children.comments" data-type="textarea">{{ training.comments }}</span>
                </div>
            </div>

            <!--
             Supports
            -->
            <!--ng-include src="'/bundles/sygefortraining/ng/training/states/detail/partials/supports.block.html'"></ng-include-->

        </div>
    </div>
</form>

<a ui-sref="training.detail.view({id: result.id})" title="{{ result.training.name }}">
    <div class="list-group-item-title">{{ result.training.name }}</div>
    <div class="list-group-item-text">{{ result.training.typeLabel }} n° {{ result.training.number }}{{ result.training.theme ? ' - ' + (result.training.theme.name ? result.training.theme.name : result.training.theme) : '' }}</div>

    <!--<p class="list-group-item-text">-->
        <!--<span><i class="fa fa-folder-open"></i> {{ result.training.theme }}</span><br>-->
        <!--<span ng-if="result.nextSession"><i class="fa fa-calendar"></i> {{ result.nextSession.dateBegin | date: 'dd/MM/yyyy' }}</span>-->
        <!--<span ng-if="result.lastSession"><i class="fa fa-calendar"></i> {{ result.lastSession.dateBegin | date: 'dd/MM/yyyy' }}</span>-->
    <!--</p>-->
</a>

<div ng-if="training" class="row" xmlns="http://www.w3.org/1999/html">
    <div class="col-md-8">
        <form sf-xeditable-form="form" sf-href='training.view({id: training.id})' on-success="onSuccess(data)">
            <span ng-include="'mycompanybundle/training/training/states/detail/partials/training.form.html'"></span>
        </form>
    </div>
    <div class="col-md-4 sidebar">
        <ng-include src="'mycompanybundle/training/training/states/detail/partials/sessions.block.html'"></ng-include>
        <div materials-block="training" entity-type="training.type"></div>
        <ng-include src="'mycompanybundle/training/training/states/detail/partials/comments.block.html'"></ng-include>
    </div>
</div>

<div class="full-height-container is-full-width is-absolute is-direction-column">

    <!-- Results -->
    <div ng-if="search.result.total" class="full-height-item is-full-width is-grow">
        <div class="col-xs-12">
            <table search-table ng-class="{loading: search.processing}">
                <thead>
                <tr>
                    <th></th>
                    <!--<th search-table-th field="training.organization.name.source">Centre</th>-->
                    <th search-table-th field="year">Année</th>
                    <th search-table-th field="semester">Semestre</th>
                    <th search-table-th field="training.number">Code</th>
                    <th search-table-th field="training.typeLabel.source">Type</th>
                    <th search-table-th field="training.name.source">Intitulé</th>
                    <th search-table-th field="training.category.source">Catégorie</th>
                    <th search-table-th field="trainers.fullName">Formateur(s)</th>
                    <th search-table-th field="sessionsCount">Sessions</th>
                    <th search-table-th field="lastSession.dateBegin">Dernière</th>
                    <th search-table-th field="nextSession.dateBegin">Prochaine</th>
                </tr>
                </thead>
                <tbody>
                <tr ng-repeat="item in search.result.items" ng-class="{warning: isSelected(item.id)}">
                    <td ng-click="switchSelect(item.id)" stop-event><i class="fa" ng-class="{'fa-square-o': !isSelected(item.id), 'fa-check-square-o': isSelected(item.id)}"></i></td>
                    <!--td>{{ item.training.organization.name.replace('Centre de', '') }}</td-->
                    <td>{{ item.year }}</td>
                    <td>{{ item.semester }}</td>
                    <td>{{ item.training.number }}</td>
                    <td>{{ item.training.typeLabel }}</td>
                    <td><a ui-sref="training.detail.view({id: item.id})">{{ item.training.name ? item.training.name : '(Sans titre)' }}</a></td>
                    <td>{{ item.category.name ? item.category.name : item.category }}</td>
                    <td>{{ item.trainers | joinObjects:'fullName' }}</td>
                    <td>{{ item.sessionsCount }}</td>
                    <td><a ui-sref="session.detail.view({id: item.lastSession.id, training: item.training.id})">{{ item.lastSession.dateBegin|date: 'dd/MM/yyyy' }}</a></td>
                    <td><a ui-sref="session.detail.view({id: item.nextSession.id, training: item.training.id})">{{ item.nextSession.dateBegin|date: 'dd/MM/yyyy' }}</a></td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div ng-if="search.result.total" class="full-height-item is-full-width">
        <div search-table-controls></div>
    </div>

    <!-- No results -->
    <div class="full-height-item is-full-width is-grow" ng-if="search.executed && !search.result.total">
        <div class="col-xs-12">
            <h1>Oops!</h1>
            <p>Il n'y a aucune formation correspondante à votre recherche.</p>
        </div>
    </div>

</div>

<div>
    <div class="mb-1">
        <a class="btn btn-xs btn-default" href="" ng-click="addDates()"><span class="fa fa-plus"></span> Ajouter une date</a>
        <br><br>

        <div ng-if="!session.dates.length" class="well well-empty well-sm">
            Il n'y a aucune date pour cette session.
        </div>

        <table ng-if="session.dates.length" class="table table-hover table-search table-condensed">
            <thead>
            <tr>
                <th>Date début</th>
                <th>Date fin</th>
                <th>Horaires matin</th>
                <th>Nombres d'heures matin</th>
                <th>Horaires après-midi</th>
                <th>Nombres d'heures après-midi</th>
                <th>Lieu</th>
                <th>Modifier</th>
                <th>Supprimer</th>
            </tr>
            </thead>
            <tbody>
            <tr ng-repeat="date in session.dates | orderBy:'dateBegin':false" >
                <!--form sf-xeditable-form="form" sf-href='dates.edit({dates: date })' on-success="onSuccess(data)"-->
                    <td>{{ date.dateBegin|date: 'dd/MM/yyyy' }}</td>
                    <td>{{ date.dateEnd|date: 'dd/MM/yyyy' }}</td>
                    <td>{{ date.scheduleMorn }}</td>
                    <td>{{ date.hourNumberMorn }}</td>
                    <td>{{ date.scheduleAfter }}</td>
                    <td>{{ date.hourNumberAfter }}</td>
                    <td>{{ date.place }}</td>
                    <td><a class="btn btn-fa" href="" ng-click="editDates(date)" tooltip="Modifier"><span class="fa fa-pencil"></span></a></td>
                    <td><a class="btn btn-fa" href="" ng-click="removeDates(date)" tooltip="Supprimer"><span class="fa fa-trash-o"></span></a></td>
                <!--/form-->
            </tr>
            </tbody>
        </table>

    </div>

</div>

<div>
    <div ng-if="!search.result.total" class="well well-empty well-sm">
        Aucun message concernant cette session n'a été envoyé.
    </div>

    <table ng-if="search.result.total" class="table table-hover table-responsive table-search.result table-condensed table-nohead">
        <!--thead>
            <tr>
                <th>Stagiaire</th>
                <th>Sujet</th>
                <th>Envoyé par</th>
                <th>Date</th>
            </tr>
        </thead-->
        <tbody>
        <tr ng-repeat="email in search.result.items">
            <td>{{ email.trainee.fullName }}</td>
            <td title="Sujet du message"><a ng-click="dislayEmail(email.id)" title="Voir le message" href="">{{ email.subject }}</a></td>
            <td title="Envoyé par {{ email.userFrom.username }}" >{{ email.userFrom.username }}</td>
            <td title="Date d'envoi">{{ email.sendAt | date: 'dd/MM/yyyy' }}</td>
        </tr>
        </tbody>
    </table>
    <div ng-if="search.result.total > search.query.size" class="full-height-item is-full-width">
        <div search-table-controls></div>
    </div>
</div>

<div>
    <div class="mb-1">
        <!-- filter -->
        <span><small>Nombre d'évaluations remplies :</small>
            <!-- stats -->
            <span class="label" ng-class="$root.sessionInscriptionStatsClass(totalEvaluatedInscriptions(), totalAcceptedInscriptions)" tooltip-placement="bottom">{{ totalEvaluatedInscriptions() }} / {{ totalAcceptedInscriptions() }}</span>
        </span>

    </div>

    <div ng-if="totalEvaluatedInscriptions() == 0" class="well well-empty well-sm">
        Il n'y a aucune evaluation pour cette session.
    </div>

    <table ng-if="totalEvaluatedInscriptions()" class="table table-hover table-search table-condensed">
        <tbody>
        <tr ng-repeat="criter in crit">
            <td> {{ criter.name }} </td>
            <td> {{ EvalAverage(criter) }} </td>
        </tr>
        </tbody>
    </table>

    <table ng-if="totalEvaluatedInscriptions()" class="table table-hover table-search table-condensed">
        <thead>
            <th>Remarques</th>
        </thead>
        <tbody>
            <tr ng-repeat="inscription in session.inscriptions" ng-if="inscription.message.length">
                <td> {{ inscription.message }} </td>
            </tr>
        </tbody>
    </table>

</div>


<div>
    <div class="mb-1">
        <!-- filter -->
        <span><small>Filtrer par statut :</small>
            <span class="btn-group dropdown">
                <a href="" class="btn btn-xs btn-default dropdown-toggle" data-toggle="dropdown">
                    <span class="text-small">{{ filterLabel || 'Tous (' + session.inscriptions.length + ')' }}</span>
                    <span class="caret"></span>
                </a>
                <ul class="dropdown-menu" role="menu">
                    <li><a href="" ng-click="resetFilter()"><i class="fa fa-check" ng-if="!filter.inscriptionStatus && !filter.presenceStatus"></i> Tous les statuts ({{ session.inscriptions.length }})</a></li>
                    <li class="divider"></li>
                    <li ng-repeat="status in status.inscription track by status.id" ng-class="{disabled :status.count == 0}"><a href="" ng-click="filterByStatus('inscription', status)"><i class="fa fa-check" ng-if="filter.inscriptionStatus.id == status.id"></i> {{ status.name }} ({{ status.count }})</a></li>
                    <li class="divider"></li>
                    <li ng-repeat="status in status.presence track by status.id" ng-class="{disabled :status.count == 0}"><a href="" ng-click="filterByStatus('presence', status)"><i class="fa fa-check" ng-if="filter.presenceStatus.id == status.id"></i> {{ status.name }} ({{ status.count }})</a></li>
                </ul>
            </span>
        </span>

        <!-- stats -->
        &nbsp;&nbsp;-&nbsp;&nbsp;<span class="label" ng-class="$root.sessionInscriptionStatsClass(totalAcceptedInscriptions(), session.maximumNumberOfRegistrations)" tooltip="{{ totalAcceptedInscriptions() }} acceptés sur {{ session.maximumNumberOfRegistrations }} places" tooltip-placement="bottom">{{ totalAcceptedInscriptions() }} / {{ session.maximumNumberOfRegistrations }}</span>

        <!-- operation -->
        <div class="pull-right">
            <a class="btn btn-xs btn-default" href="" ng-click="addInscription()"><span class="fa fa-plus"></span> Ajouter une inscription</a>
            <a class="btn btn-fa ng-scope" href="" tooltip="Agrandir la liste" ui-sref="inscription.table({session: session.id})"><span class="fa fa-external-link"></span></a>
        </div>
    </div>
    <div ng-if="!session.inscriptions.length" class="well well-empty well-sm">
        Il n'y a aucune inscription pour cette session.
    </div>

    <table ng-if="session.inscriptions.length" class="table table-hover table-search table-condensed">
        <!--thead>
            <tr>
                <th>Date</th>
                <th>Stagiaire</th>
                <th>Public</th>
                <th>Établissement</th>
                <th>Statut</th>
            </tr>
        </thead-->
        <tbody>
            <tr ng-repeat="inscription in session.inscriptions | filter:filter | orderBy:'createdAt':true">
                <td><a title="Voir l'inscription" href="" ui-sref="inscription.detail.view({ id: inscription.id, session: inscription.session.id })">{{ inscription.createdAt|date: 'dd/MM/yyyy' }}</a></td>
                <td>
                    <a title="Voir le profil du stagiaire" href="" ui-sref="trainee.detail.view({ id: inscription.trainee.id })">{{ inscription.trainee.fullName }}</a>
                </td>
                <td>{{ inscription.publicType.machineName }}</td>
                <td>{{ inscription.institution.machineName == 'other' ? inscription.otherInstitution : inscription.institution.name }}</td>
                <td>
                    <!--div class="btn-group dropdown">
                        <button class="btn btn-xs dropdown-toggle" ng-class="$root.inscriptionStatusClass(inscription.inscriptionStatus.status, 'btn')" data-toggle="dropdown">{{ inscription.inscriptionStatus.name }} <span class="caret"></span></button>
                        <ul class="dropdown-menu text-small" role="menu">
                            <li ng-repeat="sta in status.inscription track by sta.id" ng-class="{disabled :inscription.inscriptionStatus.id == sta.id}"><a href="" ng-click="updateInscriptionStatus(inscription, sta)"><i class="fa fa-check" ng-if="inscription.inscriptionStatus.id == sta.id"></i> {{ sta.name }}</a></li>
                        </ul>
                    </div>

                    <div ng-if="inscription.presenceStatus || inscription.inscriptionStatus.status == 2" class="btn-group dropdown">
                        <button class="btn btn-xs dropdown-toggle" ng-class="$root.presenceStatusClass(inscription.presenceStatus.status, 'btn')" data-toggle="dropdown">
                            <span ng-if="inscription.presenceStatus">{{ inscription.presenceStatus.name }}</span>
                            <span ng-if="!inscription.presenceStatus"><em>Statut de présence</em></span>
                            <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu text-small">
                            <li ng-repeat="stat in status.presence track by stat.id" ng-class="{disabled :inscription.presenceStatus.id == stat.id}"><a href="" ng-click="updatePresenceStatus(inscription, stat)"><i class="fa fa-check" ng-if="inscription.presenceStatus.id == stat.id"></i> {{ stat.name }}</a></li>
                        </ul>
                    </div-->

                    <!-- editable -->
                    <div class="btn-group dropdown">
                        <button class="btn btn-xs dropdown-toggle" ng-if="!inscription.presenceStatus || inscription.inscriptionStatus.status!=2" class="label" ng-class="$root.inscriptionStatusClass(inscription.inscriptionStatus.status, 'btn')" data-toggle="dropdown">
                            {{ inscription.inscriptionStatus.name }}
                            <span class="caret"></span>
                        </button>
                        <button class="btn btn-xs dropdown-toggle" ng-if="inscription.presenceStatus &&  inscription.inscriptionStatus.status==2" class="label" ng-class="$root.presenceStatusClass(inscription.presenceStatus.status, 'btn')" data-toggle="dropdown">
                            {{ inscription.presenceStatus.name }}
                            <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu text-small" role="menu">
                            <li class="dropdown-header">Statuts d'inscription</li>
                            <li ng-repeat="status in status.inscription track by status.id" ng-class="{disabled :inscription.inscriptionStatus.id == status.id}"><a href="" ng-click="updateInscriptionStatus(inscription, status)"><i class="fa fa-check" ng-if="inscription.inscriptionStatus.id == status.id"></i> {{ status.name }}</a></li>

                            <li ng-if="inscription.inscriptionStatus.status == 2" class="divider"></li>
                            <li ng-if="inscription.inscriptionStatus.status == 2" class="dropdown-header">Statuts de présence</li>
                            <li ng-if="inscription.inscriptionStatus.status == 2" ng-repeat="status in status.presence track by status.id" ng-class="{disabled :inscription.presenceStatus.id == status.id}"><a href="" ng-click="updatePresenceStatus(inscription, status)"><i class="fa fa-check" ng-if="inscription.presenceStatus.id == status.id"></i> {{ status.name }}</a></li>

                            <li class="divider"></li>
                            <li><a href="" ng-click="delete(inscription)">Supprimer cette inscription</a></li>
                        </ul>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>

</div>

<div>
    <div class="mb-1">
        <!-- filter -->
        <span><small>Filtrer par statut :</small>
            <span class="btn-group dropdown">
                <a href="" class="btn btn-xs btn-default dropdown-toggle" data-toggle="dropdown">
                    <span class="text-small">{{ filterLabel || 'Tous (' + session.inscriptions.length + ')' }}</span>
                    <span class="caret"></span>
                </a>
                <ul class="dropdown-menu" role="menu">
                    <li><a href="" ng-click="resetFilter()"><i class="fa fa-check" ng-if="!filter.inscriptionStatus && !filter.presenceStatus"></i> Tous les statuts ({{ session.inscriptions.length }})</a></li>
                    <li class="divider"></li>
                    <li ng-repeat="status in status.inscription track by status.id" ng-class="{disabled :status.count == 0}"><a href="" ng-click="filterByStatus('inscription', status)"><i class="fa fa-check" ng-if="filter.inscriptionStatus.id == status.id"></i> {{ status.name }} ({{ status.count }})</a></li>
                    <li class="divider"></li>
                    <li ng-repeat="status in status.presence track by status.id" ng-class="{disabled :status.count == 0}"><a href="" ng-click="filterByStatus('presence', status)"><i class="fa fa-check" ng-if="filter.presenceStatus.id == status.id"></i> {{ status.name }} ({{ status.count }})</a></li>
                </ul>
            </span>
        </span>

        <!-- stats -->
        &nbsp;&nbsp;-&nbsp;&nbsp;<span class="label" ng-class="$root.sessionInscriptionStatsClass(totalAcceptedInscriptions(), session.maximumNumberOfRegistrations)" tooltip="{{ totalAcceptedInscriptions() }} acceptés sur {{ session.maximumNumberOfRegistrations }} places" tooltip-placement="bottom">{{ totalAcceptedInscriptions() }} / {{ session.maximumNumberOfRegistrations }}</span>

        <!-- operation -->
        <div class="pull-right">
            <a ng-if="session._accessRights.edit" class="btn btn-xs btn-default" href="" ng-click="addInscription()"><span class="fa fa-plus"></span> Ajouter une inscription</a>
            <a class="btn btn-fa ng-scope" href="" tooltip="Agrandir la liste" ui-sref="inscription.table({session: session.id})"><span class="fa fa-external-link"></span></a>
        </div>
    </div>
    <div ng-if="!session.inscriptions.length" class="well well-empty well-sm">
        Il n'y a aucune inscription pour cette session.
    </div>

    <table ng-if="session.inscriptions.length" class="table table-hover table-search table-condensed">
        <!--thead>
            <tr>
                <th>Date</th>
                <th>Stagiaire</th>
                <th>Public</th>
                <th>Établissement</th>
                <th>Statut</th>
            </tr>
        </thead-->
        <tbody>
            <tr ng-repeat="inscription in session.inscriptions | filter:filter | orderBy:'createdAt':true">
                <td><a title="Voir l'inscription" href="" ui-sref="inscription.detail.view({ id: inscription.id, session: inscription.session.id })">{{ inscription.createdAt|date: 'dd/MM/yyyy' }}</a></td>
                <td>
                    <a ng-if="inscription.trainee._accessRights.view" title="Voir le profil du stagiaire" href="" ui-sref="trainee.detail.view({ id: inscription.trainee.id })">{{ inscription.trainee.fullName }}</a>
                    <span ng-if="!inscription.trainee._accessRights.view">{{ inscription.trainee.fullName }}</span>
                </td>
                <td>{{ inscription.publicCategory.name }}</td>
                <td>{{ inscription.institution.machineName == 'other' ? inscription.otherInstitution : inscription.institution.name }}</td>
                <td>
		<div class="ng-binding">
			<div class="btn-group dropdown">
                		<button class="btn btn-xs dropdown-toggle" ng-class="$root.inscriptionStatusClass(inscription.inscriptionStatus.status, 'btn')" data-toggle="dropdown">{{ inscription.inscriptionStatus.name }} <span class="caret"></span></button>
                		<ul class="dropdown-menu text-small">
                    			<li ng-repeat="status in status.inscription track by status.id" ng-class="{disabled :inscription.inscriptionStatus.id == status.id}"><a href="" ng-click="updateInscriptionStatus(inscription,status)"><i class="fa fa-check" ng-if="inscription.inscriptionStatus.id == status.id"></i> {{ status.name }}</a></li>
                		</ul>
            		</div>

            		<div ng-if="inscription.presenceStatus || inscription.inscriptionStatus.status == 2" class="btn-group dropdown">
                		<button class="btn btn-xs dropdown-toggle" ng-class="$root.presenceStatusClass(inscription.presenceStatus.status, 'btn')" data-toggle="dropdown">
                    			<span ng-if="inscription.presenceStatus">{{ inscription.presenceStatus.name }}</span>
                    			<span ng-if="!inscription.presenceStatus"><em>Statut de présence</em></span>
                    			<span class="caret"></span>
                		</button>
                		<ul class="dropdown-menu text-small">
                    			<li ng-repeat="status in status.presence track by status.id" ng-class="{disabled :inscription.presenceStatus.id == status.id}"><a href="" ng-click="updatePresenceStatus(inscription,status)"><i class="fa fa-check" ng-if="inscription.presenceStatus.id == status.id"></i> {{ status.name }}</a></li>
                		</ul>
            		</div>
		</div>
		</td>
            </tr>
        </tbody>
    </table>
</div>

<ul class="summary">
    <li ng-repeat="public in publics">
        {{ public.name }}
        <span class="pull-right inline-block" sf-xeditable="getFormElement(public)" data-mode="popup" data-placement="left">{{ getCount(public) }}</span>
    </li>
</ul>

<div class="block block-comments">
    <div class="block-title">
        <span class="h4"><span class="fa fa-comment-o"></span> Commentaires</span>
    </div>
    <div class="block-body">
        <form sf-xeditable-form="form" sf-href='training.view({id: training.id})' on-success="onSuccess(data)">
            <p><span sf-xeditable="form.children.comments" data-type="textarea">{{ training.comments }}</span></p>
        </form>
    </div>
</div>

<div class="block block-module-sessions">
    <div class="block-title">
        <div class="full-width">
            <a ui-sref="session.table({training: training.id})" class="btn btn-link h4"><span class="fa fa-calendar-o"></span>Sessions / module</a>
            <div class="pull-right">
                <a ng-if="training._accessRights.edit" class="btn btn-fa btn-sm" href="" tooltip="Ajouter une session" ng-click="addSession()"><span class="fa fa-plus"></span></a>
                <span class="badge" tooltip="{{ training.sessions.length}} session(s)">{{ training.sessions.length}}</span>
            </div>
        </div>
    </div>
    <div class="block-body">
        <!-- display sessions without module -->
        <div ng-if="sessionsWithoutModule.length > 0">
            <div class="text-muted">Session(s) sans module</div>
            <ul class="list-circle">
                <li ng-repeat="session in sessionsWithoutModule | orderBy:'dateBegin':true" ng-class="{past: $moment(session.dateBegin) < $moment()}">
                    <a ui-sref="session.detail.view({training: training.id, id: session.id})">{{ session.dateBegin | date:'EEEE d MMMM y' }} - {{ session.name }}</a>

                    <!-- stats -->
                    &nbsp;&nbsp;
                    <span registration-label="session"></span>

                    <div class="pull-right">
                        <a registration-stats-label="session" class="label-lg" tooltip-placement="left"></a>
                    </div>
                </li>
            </ul>
        </div>

        <!--display module sessions-->
        <div ng-repeat="module in training.modules | orderBy: 'name' : false">
            <a tooltip="{{ module.name + (module.mandatory ? ' obligatoire' : ' non obligatoire') }}" ng-if="training._accessRights.edit" href="" class="text-muted" ng-click="editModule(module)">
                {{ module.name }}
                <span class="fa" ng-class="{'fa-check-square-o': module.mandatory, 'fa-square-o': !module.mandatory}"></span>
            </a>
            <div tooltip="{{ module.name + (module.mandatory ? ' obligatoire' : ' non obligatoire') }}" ng-if="!training._accessRights.edit" class="text-muted">
                {{ module.name }}
                <span class="fa" ng-class="{'fa-check-square-o': module.mandatory, 'fa-square-o': !module.mandatory}"></span>
            </div>
            <ul class="list-circle">
                <li ng-repeat="session in module.sessions | orderBy:'dateBegin':true" ng-class="{past: $moment(session.dateBegin) < $moment()}">
                    <a ui-sref="session.detail.view({training: training.id, id: session.id})">{{ session.dateBegin | date:'EEEE d MMMM y' }} - {{ session.name }}</a>

                    <!-- stats -->
                    &nbsp;&nbsp;
                    <span registration-label="session"></span>

                    <div class="pull-right">
                        <a registration-stats-label="session" class="label-lg" tooltip-placement="left"></a>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</div>

<div class="block block-sessions">

    <div class="block-title">
        <div class="full-width">
            <a ui-sref="session.table({training: training.id})" class="btn btn-link h4"><span class="fa fa-calendar-o"></span>Sessions</a>
            <div class="pull-right">
                <a ng-if="training._accessRights.edit" class="btn btn-fa btn-sm" href="" tooltip="Ajouter une session" ng-click="addSession()"><span class="fa fa-plus"></span></a><!--
                --><span class="badge">{{ training.sessions.length}}</span>
            </div>
        </div>
    </div>
    <div class="block-body">
        <div class="well well-empty well-sm" ng-if="!training.sessions.length">
            Il n'y a aucune session passée ou à venir.
        </div>
        <ul class="list-unstyled list-padded">
            <li ng-repeat="session in training.sessions | orderBy:'dateBegin':true" ng-class="{past: $moment(session.dateBegin) < $moment()}">
                <a ui-sref="session.detail.view({training: training.id, id: session.id})">{{ session.dateBegin | date:'EEEE d MMMM y' }} - {{ session.name }}</a>

                <!-- stats -->
                &nbsp;&nbsp;
                <span registration-label="session"></span>

                <div class="pull-right">
                    <a registration-stats-label="session" class="label-lg" tooltip-placement="left"></a>
                </div>
            </li>
        </ul>
    </div>
</div>

<div class="btn-group pull-right">
    <a class="btn btn-fa" href="" tooltip="Bilan" ng-if="training.sessions.length && training._accessRights.view" ng-click="getBalanceSheet()"><span class="fa fa-file-excel-o"></span></a>
    <a class="btn btn-fa" href="" tooltip="Publipostage" ng-click="$dialog.open('batch.publipost', { items: [training.id], service: 'training' })"><span class="fa fa-file-word-o"></span></a>
    <a class="btn btn-fa" href="" tooltip="Dupliquer" ng-if="training._accessRights.edit" ng-click="duplicate()"><span class="fa fa-copy"></span></a>
    <a class="btn btn-fa" href="" tooltip="Supprimer" ng-if="training._accessRights.delete" ng-click="delete()"><span class="fa fa-trash-o"></span></a>
</div>

<div class="pre-title">{{ training.typeLabel }} n°{{ training.number }} -  {{ training.organization.name }}</div>

<h2><span sf-xeditable="form.children.name" data-type="text">{{ training.name }}<!-- action button --></span></h2>

<div class="infos">
    <label>Thème :</label> <span sf-xeditable="form.children.theme" data-type="select">{{ training.theme.name }}</span><br/>
    <div><label>Tags :</label> <span sf-xeditable="form.children.tags" data-type="select2">{{ training.tags|joinObjects:'name' }}</span></div>
</div>

<h3>Objectifs</h3><hr>
<p><span sf-xeditable="form.children.description" data-type="textarea">{{ training.description }}</span></p>

<h3>Programme</h3><hr>
<p><span sf-xeditable="form.children.program" data-type="textarea">{{ training.program }}</span></p>


<div class="modal-header">
    <button type="button" class="close" ng-click="dialog.dismiss()">×</button>
    <h4 class="modal-title">
        Visualisation du message
        <span ng-if="email.session">
            concernant la session <strong>{{ email.session.training.name }}</strong> du <strong>{{ email.session.dateBegin | date: 'dd/MM/yyyy' }}</strong>
        </span>
    </h4>
</div>
<div class="modal-body">
    <div class="form-horizontal">
        <div class="form-group">
            <label class="col-sm-3 control-label">Sujet</label>
            <div class="col-md-9">
                <div class="form-control" style="height: auto">
                    {{ email.subject }}
                </div>
            </div>
        </div>

        <div class="form-group">
            <label class="col-sm-3 control-label">Message</label>
            <div class="col-md-9">
                <div ng-bind-html="email.body | nl2br" class="form-control" style="height: auto">
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <span class="pull-right">
                <em>Envoyé par {{ email.userFrom.username }}<{{ email.emailFrom }}> à {{ email.trainee ? email.trainee.fullName : (email.trainer ? email.trainer.fullName : '') }} le {{ email.sendAt | date: 'dd/MM/yyyy' }}</em>
            </span>
        </div>
    </div>
</div>
<div class="modal-footer">
    <a class="btn btn-primary" ng-click="dialog.dismiss()">Fermer</a>
</div>

<div class="modal-header">
    <button type="button" class="close" ng-click="dialog.dismiss()" aria-hidden="true">×</button>
    <h4 class="modal-title"><i class="fa fa-graduation-cap"></i> Ajouter une inscription</h4>
</div>

<form sf-href="inscription.create({session: dialog.params.session.id})" sf-form="form" json-path="form" on-success="onSuccess(data)" class="form-horizontal" novalidate>
    <div class="modal-body">
        <div class="form-group" ng-class="{'has-error': form.children.trainee.errors.length }">
            <label class="col-sm-3 control-label" for="{{ form.children.trainee.id }}">{{ form.children.trainee.label }}</label>
            <div class="col-sm-9">
               <div class="input-group" ng-if="userCanAddTrainee()">
                   <input placeholder="Cliquez pour rechercher un stagiaire..." type="text" typeahead-template-url="mycompanybundle/inscription/dialogs/typeahead-trainee.html" required="required" typeahead-wait-ms="200" typeahead="choice as choice.label for choice in getTraineeList($viewValue)" typeahead-editable="false" class="form-control" typeahead-on-select="setTrainee($item)" ng-model="$parent.selectedTrainee" />
                    <span class="input-group-btn">
                        <button tooltip="Créer un nouveau stagiaire" tooltip-placement="bottom" class="btn btn-default" ng-click="createUser()" type="button"><i class="fa fa-plus"></i></button>
                    </span>
               </div>
               <input placeholder="Cliquez pour rechercher un stagiaire..." ng-if="!userCanAddTrainee()" type="text" typeahead-template-url="mycompanybundle/inscription/dialogs/typeahead-trainee.html" required="required" typeahead-wait-ms="200" typeahead="choice as choice.label for choice in getTraineeList($viewValue)" typeahead-editable="false" class="form-control" typeahead-on-select="setTrainee($item)" ng-model="$parent.selectedTrainee" />
                   <div ng-if="error.length" ng-repeat="error in form.children.trainee.errors" class="help-block">{{ error }}</div>
            </div>
        </div>
        <div class="form-group" ng-class="{'has-error': form.children.inscriptionStatus.errors.length }">
            <label class="col-sm-3 control-label" for="{{ form.children.inscriptionStatus.id }}">{{ form.children.inscriptionStatus.label }}</label>
            <div class="col-sm-9">
                <span sf-form-widget="form.children.inscriptionStatus" class="form-control"/>
                <div ng-if="error.length" ng-repeat="error in form.children.inscriptionStatus.errors" class="help-block">{{ error }}</div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <a class="btn btn-default" ng-click="dialog.dismiss()">Annuler</a>
        <input class="btn btn-primary" type="submit" value="Ajouter" />
    </div>
</form>

<div class="modal-header">
    <button type="button" class="close" ng-click="dialog.dismiss()" aria-hidden="true">×</button>
    <h4 class="modal-title"><i class="fa fa-warning"></i> Supprimer une inscription</h4>
</div>
<div class="modal-body center-block">
    <div class="alert alert-danger" role="alert">
        Êtes-vous certain de vouloir supprimer l'inscription du stagiaire <strong>{{ inscription.trainee.fullName }}</strong> à la session du <strong>{{ inscription.session.dateBegin|date:'dd/MM/yy' }}</strong> de la formation <strong>{{ inscription.session.training.name }}</strong>?
    </div>
</div>
<div class="modal-footer">
    <a class="btn btn-default" ng-click="dialog.dismiss()">Annuler</a>
    <a class="btn btn-primary" ng-click="ok()">Valider</a>
</div>

<a class="typeahead-inscription-item">
 <h4 class="item-name">{{match.label}}</h4>
    <div ng-if="match.model.institution != ''" class="item-complement row"><span class="col-xs-6">{{match.model.institution }}</span><span class="small col-xs-6 pull-right inline-block">({{match.model.organization }})</span></div>
    <p ng-if="match.model.institution == ''" class="item-complement">{{match.model.organization }}</p>

</a>
<div ng-if="items.length">
    <table class="table table-hover table-search table-condensed">
        <thead>
        <tr>
            <th>Date</th>
            <th>Stagiaire</th>
            <th>Session</th>
            <th>Type de personnel</th>
        </tr>
        </thead>
        <tbody>
        <tr ng-repeat="item in items">
            <td><a href="" ui-sref="inscription.detail.view({ id: item.id, session: item.session.id })" ui-sref-widget-opts>{{ item.inscriptionStatusUpdatedAt|date: 'dd/MM/yyyy' }}</a></td>
            <td><a href="" ui-sref="trainee.detail.view({ id: item.trainee.id })">{{ item.trainee.fullName }}</a></td>
            <td><a href="" ui-sref="session.detail.view({ id: item.session.id, training: item.session.training.id })">{{ item.session.dateBegin|date: 'dd/MM/yyyy' }} - {{ item.session.training.name }}</a></td>
            <td>{{ item.trainee.publicCategory }}</td>
        </tr>
        </tbody>
    </table>
    <pagination ng-if="search.result.total > search.query.size" total-items="search.result.total" items-per-page="search.query.size" ng-model="search.query.page" class="pagination-sm" max-size="5" previous-text="&lsaquo;" next-text="&rsaquo;"></pagination>
</div>

<div class="widget-empty-msg" ng-if="!items.length && !loading">{{ options.emptymsg || 'Aucune inscription dans cette liste' }}</div>

<div ng-if="items.length">
    <table class="table table-hover table-search table-condensed">
        <thead>
        <tr>
            <th>Date</th>
            <th>Stagiaire</th>
            <th>Session</th>
            <th>Type de personnel</th>
            <th>Inscription</th>
        </tr>
        </thead>
        <tbody>
            <tr ng-repeat="item in items">
                <td><a href="" ui-sref="inscription.detail.view({ id: item.id, session: item.session.id })" ui-sref-widget-opts>{{ item.createdAt|date: 'dd/MM/yyyy' }}</a></td>
                <td><a href="" ui-sref="trainee.detail.view({ id: item.trainee.id })">{{ item.trainee.fullName }}</a></td>
                <td><a href="" ui-sref="session.detail.view({ id: item.session.id, training: item.session.training.id })">{{ item.session.dateBegin|date: 'dd/MM/yyyy' }} - {{ item.session.training.name }}</a></td>
                <td>{{ item.trainee.publicCategory }}</td>
                <td><span class="label" ng-class="$root.inscriptionStatusClass(item.inscriptionStatus.status)">{{ item.inscriptionStatus.name }}</span></td>
            </tr>
        </tbody>
    </table>
    <pagination ng-if="search.result.total > search.query.size" total-items="search.result.total" items-per-page="search.query.size" ng-model="search.query.page" class="pagination-sm" max-size="5" previous-text="&lsaquo;" next-text="&rsaquo;"></pagination>
</div>

<div class="widget-empty-msg" ng-if="!items.length && !loading">{{ options.emptymsg || 'Aucune inscription dans cette liste' }}</div>

<div class="modal-header">
    <button type="button" class="close" ng-click="dialog.dismiss()" aria-hidden="true">×</button>
    <h4 class="modal-title"><i class="fa fa-warning"></i> Change l'activation du compte</h4>
</div>
<div class="modal-body center-block">
    <div class="alert alert-danger" role="alert">
        Êtes-vous certain de vouloir {{ dialog.params.trainee.isActive ? 'invalider' : 'valider' }} le compte de <strong>{{ dialog.params.trainee.fullName }}</strong> ?
    </div>
</div>
<div class="modal-footer">
    <a class="btn btn-default" ng-click="dialog.dismiss()">Annuler</a>
    <a class="btn btn-primary" ng-click="ok()">Valider</a>
</div>

<div class="modal-header">
    <button type="button" class="close" ng-click="dialog.dismiss()" aria-hidden="true">×</button>
    <h4 class="modal-title"><i class="fa fa-university"></i> Changer le centre de rattachement</h4>
</div>
<form sf-href="trainee.changeorg({id: dialog.params.trainee.id})" sf-form="form" json-path="form" on-success="onSuccess(data)" class="form-horizontal" novalidate>
    <div class="modal-body center-block">
        <div ng-repeat="key in ['organization', 'institution']">
            <div class="form-group" ng-if="form.children[key]" ng-class="{'has-error': form.children[key].errors.length }">
                <label class="col-sm-3 control-label" for="{{ form.children[key].id }}">{{ form.children[key].label }}</label>
                <div class="col-sm-9">
                    <span sf-form-widget="form.children[key]" class="form-control"/>
                    <div ng-if="error.length" ng-if="error.length" ng-repeat="error in form.children[key].errors" class="help-block">{{ error }}</div>
                </div>
            </div>
        </div>
        <div class="form-group col-sm-9 col-sm-offset-3" ng-if="form.errors.length" ng-class="{'has-error': form.errors.length }">
            <div ng-repeat="error in form.errors" class="help-block">{{ error }}</div>
        </div>
    </div>
    <div class="modal-footer">
        <a class="btn btn-default" ng-click="dialog.dismiss()">Annuler</a>
        <input class="btn btn-primary" type="submit" value="Valider" />
    </div>
</form>

<div class="modal-header">
    <button type="button" class="close" ng-click="dialog.dismiss()" aria-hidden="true">×</button>
    <h4 class="modal-title"><i class="fa fa-unlock-alt"></i> Changer le mot de passe</h4>
</div>
<form sf-href="trainee.changepwd({id: dialog.params.trainee.id})" sf-form="form" json-path="form" on-success="onSuccess(data)" class="form-horizontal" novalidate>
    <div class="modal-body center-block">
        <div class="form-group" ng-class="{'has-error': form.children.plainPassword.children.first.errors.length }">
            <label class="col-sm-4 control-label" for="{{ form.children.plainPassword.children.first.id }}">Nouveau mot de passe</label>
            <div class="col-sm-8">
                <span sf-form-widget="form.children.plainPassword.children.first" class="form-control"/>
                <div ng-if="error.length" ng-if="error.length" ng-repeat="error in form.children.plainPassword.children.first.errors" class="help-block">{{ error }}</div>
            </div>
        </div>
        <div class="form-group" ng-class="{'has-error': form.children.plainPassword.children.second.errors.length }">
            <label class="col-sm-4 control-label" for="{{ form.children.plainPassword.children.second.id }}">Confirmation</label>
            <div class="col-sm-8">
                <span sf-form-widget="form.children.plainPassword.children.second" class="form-control"/>
                <div ng-if="error.length" ng-if="error.length" ng-repeat="error in form.children.plainPassword.children.second.errors" class="help-block">{{ error }}</div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <a class="btn btn-default" ng-click="dialog.dismiss()">Annuler</a>
        <input class="btn btn-primary" type="submit" value="Valider" />
    </div>
</form>

<div class="modal-header">
    <button type="button" class="close" ng-click="dialog.dismiss()" aria-hidden="true">×</button>
    <h4 class="modal-title"><i class="fa fa-user"></i> Ajouter un stagiaire</h4><br>
    <h4 class="modal-title"><i class="fa fa-warning"></i> Attention : lorsque vous ajoutez manuellement un stagiaire, les informations saisies sont potentiellement incomplètes ou erronées. Cela entraîne des erreurs dans les statistiques !</h4>
</div>

<form sf-href="trainee.create" sf-form="form" json-path="form" on-success="onSuccess(data)" class="form-horizontal" novalidate>
    <div class="modal-body">

        <div ng-repeat="key in ['organization', 'title', 'lastName', 'firstName', 'email', 'birthDate', 'phoneNumber', 'address', 'zip', 'city', 'institution', 'service', 'amuStatut', 'bap', 'corps', 'category', 'campus', 'fonction']">
            <div class="form-group" ng-if="form.children[key]" ng-class="{'has-error': form.children[key].errors.length }">
                <label class="col-sm-3 control-label" for="{{ form.children[key].id }}">{{ form.children[key].label }}</label>
                <div class="col-sm-9">
                    <span sf-form-widget="form.children[key]" class="form-control" />
                    <div ng-if="error.length" ng-if="error.length" ng-repeat="error in form.children[key].errors" class="help-block">{{ error }}</div>
                </div>
            </div>
        </div>
        <div>
            <div class="form-group" ng-if="form.children['publicType']" ng-class="{'has-error': form.children['publicType'].errors.length }">
                <label class="col-sm-3 control-label" for="{{ form.children['publicType'].id }}">{{ form.children['publicType'].label }}</label>
                <div class="col-sm-9">
                    <span sf-form-widget="form.children['publicType']" ng-required="true" class="form-control" />
                    <div ng-if="error.length" ng-if="error.length" ng-repeat="error in form.children['publicType'].errors" class="help-block">{{ error }}</div>
                </div>
            </div>
        </div>
        <h3 class="modal-header">Responsable hiérarchique</h3>
        <div ng-repeat="key in ['lastNameSup', 'firstNameSup', 'emailSup']">
            <div class="form-group" ng-if="form.children[key]" ng-class="{'has-error': form.children[key].errors.length }" >
                <label class="col-sm-3 control-label" for="{{ form.children[key].id }}">{{ form.children[key].label }}</label>
                <div class="col-sm-9">
                    <span sf-form-widget="form.children[key]" class="form-control"/>
                    <div ng-if="error.length" ng-if="error.length" ng-repeat="error in form.children[key].errors" class="help-block">{{ error }}</div>
                </div>
            </div>
        </div>
        <h3 class="modal-header">Correspondant formation</h3>
        <div ng-repeat="key in ['lastNameCorr', 'firstNameCorr', 'emailCorr']">
            <div class="form-group" ng-if="form.children[key]" ng-class="{'has-error': form.children[key].errors.length }" >
                <label class="col-sm-3 control-label" for="{{ form.children[key].id }}">{{ form.children[key].label }}</label>
                <div class="col-sm-9">
                    <span sf-form-widget="form.children[key]" class="form-control"/>
                    <div ng-if="error.length" ng-if="error.length" ng-repeat="error in form.children[key].errors" class="help-block">{{ error }}</div>
                </div>
            </div>
        </div>

    </div>
    <div class="modal-footer">
        <a class="btn btn-default" ng-click="dialog.dismiss()">Annuler</a>
        <input class="btn btn-primary" type="submit" value="Enregistrer" />
    </div>
</form>

<div class="modal-header">
    <button type="button" class="close" ng-click="dialog.dismiss()" aria-hidden="true">×</button>
    <h4 class="modal-title"><i class="fa fa-warning"></i> Supprimer un stagiaire</h4>
</div>
<div class="modal-body center-block">
    <div class="alert alert-danger" role="alert">
        Êtes-vous certain de vouloir supprimer le stagiaire <strong>{{ dialog.params.trainee.fullName }}</strong> ?
    </div>
</div>
<div class="modal-footer">
    <a class="btn btn-default" ng-click="dialog.dismiss()">Annuler</a>
    <a class="btn btn-primary" ng-click="ok()">Valider</a>
</div>

<div class="modal-header">
    <button type="button" class="close" ng-click="dialog.dismiss()" aria-hidden="true">×</button>
    <h4 class="modal-title"><i class="fa fa-university"></i> Changer le centre de rattachement</h4>
</div>
<form sf-href="institution.changeorg({id: dialog.params.institution.id})" sf-form="form" json-path="form" on-success="onSuccess(data)" class="form-horizontal" novalidate>
    <div class="modal-body center-block">
        <div ng-repeat="key in ['organization']">
            <div class="form-group" ng-if="form.children[key]" ng-class="{'has-error': form.children[key].errors.length || form.errors.length }">
                <label class="col-sm-3 control-label" for="{{ form.children[key].id }}">{{ form.children[key].label }}</label>
                <div class="col-sm-9">
                    <span sf-form-widget="form.children[key]" class="form-control"/>
                    <div ng-if="error.length" ng-if="error.length" ng-repeat="error in form.children[key].errors" class="help-block">{{ error }}</div>
                    <div ng-if="error.length" ng-if="error.length" ng-repeat="error in form.errors" class="help-block">{{ error }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <a class="btn btn-default" ng-click="dialog.dismiss()">Annuler</a>
        <input class="btn btn-primary" type="submit" value="Valider" />
    </div>
</form>

<div class="modal-header">
    <button type="button" class="close" ng-click="dialog.dismiss()" aria-hidden="true">×</button>
    <h4 class="modal-title"><i class="fa fa-user"></i> Ajouter un établissement</h4>
</div>

<form sf-href="institution.create" sf-form="form" json-path="form" on-success="onSuccess(data)" class="form-horizontal" novalidate>
    <div class="modal-body">

        <div ng-repeat="key in ['organization', 'name', 'zip', 'city']">
            <div class="form-group" ng-if="form.children[key]" ng-class="{'has-error': form.children[key].errors.length }">
                <label class="col-sm-3 control-label" for="{{ form.children[key].id }}">{{ form.children[key].label }}</label>
                <div class="col-sm-9">
                    <span sf-form-widget="form.children[key]" class="form-control"/>
                    <div ng-if="error.length" ng-repeat="error in form.children[key].errors" class="help-block">{{ error }}</div>
                </div>
            </div>
        </div>

    </div>
    <div class="modal-footer">
        <a class="btn btn-default" ng-click="dialog.dismiss()">Annuler</a>
        <input class="btn btn-primary" type="submit" value="Enregistrer" />
    </div>
</form>

<div class="modal-header">
    <button type="button" class="close" ng-click="dialog.dismiss()" aria-hidden="true">×</button>
    <h4 class="modal-title"><i class="fa fa-warning"></i> Supprimer un établissement</h4>
</div>
<div class="modal-body center-block">
    <div class="alert alert-danger" role="alert">
        Êtes-vous certain de vouloir supprimer l'établissement <strong>{{ dialog.params.institution.name }}</strong> ?
    </div>
</div>
<div class="modal-footer">
    <a class="btn btn-default" ng-click="dialog.dismiss()">Annuler</a>
    <a class="btn btn-primary" ng-click="ok()">Valider</a>
</div>

<div class="modal-header">
    <button type="button" class="close" ng-click="dialog.dismiss()" aria-hidden="true">×</button>
    <h4 class="modal-title"><i class="fa fa-warning"></i> Change la validation</h4>
</div>
<div class="modal-body center-block">
    <div class="alert alert-danger" role="alert">
        Êtes-vous certain de vouloir {{ dialog.params.institution.validated ? 'invalider' : 'valider' }} l'établissement <strong>{{ dialog.params.institution.name }}</strong> ?
    </div>
</div>
<div class="modal-footer">
    <a class="btn btn-default" ng-click="dialog.dismiss()">Annuler</a>
    <a class="btn btn-primary" ng-click="ok()">Valider</a>
</div>

<div ng-if="items.length">
    <table class="table table-hover table-search table-condensed">
        <thead>
        <tr>
            <th>Date</th>
            <th>Nom</th>
            <th>Type de personnel</th>
            <th>Établissement</th>
            <th>Activé</th>
        </tr>
        </thead>
        <tbody>
            <tr ng-repeat="item in items">
                <td>{{ item.createdAt|date: 'dd/MM/yyyy' }}</td>
                <td><a href="" ui-sref="trainee.detail.view({ id: item.id })" ui-sref-widget-opts>{{ item.fullName }}</a></td>
                <td>{{ item.publicCategory }}</td>
                <td>{{ item.institution.name }}</td>
                <td>
                    <span class="label" ng-class="{'label-danger' : item.isActive == false, 'label-default' : item.isActive == true}">
                        {{ item.isActive ? 'Oui' : 'Non' }}
                    </span>
                </td>
            </tr>
        </tbody>
    </table>
    <pagination ng-if="search.result.total > search.query.size" total-items="search.result.total" items-per-page="search.query.size" ng-model="search.query.page" class="pagination-sm" max-size="5" previous-text="&lsaquo;" next-text="&rsaquo;"></pagination>
</div>

<div class="widget-empty-msg" ng-if="!items.length && !loading">{{ options.emptymsg || 'Aucun stagiaire dans cette liste' }}</div>

<div class="modal-header">
    <button type="button" class="close" ng-click="dialog.dismiss()" aria-hidden="true">×</button>
    <h4 class="modal-title"><i class="fa fa-university"></i> Changer le centre de rattachement</h4>
</div>
<form sf-href="trainer.changeorg({id: dialog.params.trainer.id})" sf-form="form" json-path="form" on-success="onSuccess(data)" class="form-horizontal" novalidate>
    <div class="modal-body center-block">
        <div ng-repeat="key in ['organization', 'institution']">
            <div class="form-group" ng-if="form.children[key]" ng-class="{'has-error': form.children[key].errors.length }">
                <label class="col-sm-3 control-label" for="{{ form.children[key].id }}">{{ form.children[key].label }}</label>
                <div class="col-sm-9">
                    <span sf-form-widget="form.children[key]" class="form-control"/>
                    <div ng-repeat="error in form.children[key].errors" class="help-block">{{ error }}</div>
                </div>
            </div>
        </div>
        <div class="form-group col-sm-9 col-sm-offset-3" ng-if="form.errors.length" ng-class="{'has-error': form.errors.length }">
            <div ng-repeat="error in form.errors" class="help-block">{{ error }}</div>
        </div>
    </div>
    <div class="modal-footer">
        <a class="btn btn-default" ng-click="dialog.dismiss()">Annuler</a>
        <input class="btn btn-primary" type="submit" value="Valider" />
    </div>
</form>

<div class="modal-header">
    <button type="button" class="close" ng-click="dialog.dismiss()" aria-hidden="true">×</button>
    <h4 class="modal-title"><i class="fa fa-user"></i> Ajouter un formateur</h4>
</div>

<form sf-href="trainer.create" sf-form="form" json-path="form" on-success="onSuccess(data)" class="form-horizontal" novalidate>
    <div class="modal-body">

        <div ng-repeat="key in ['organization', 'title', 'lastName', 'firstName', 'email', 'isOrganization']">
            <div class="form-group" ng-if="form.children[key]" ng-class="{'has-error': form.children[key].errors.length }">
                <label class="col-sm-3 control-label" for="{{ form.children[key].id }}">{{ form.children[key].label }}</label>
                <div class="col-sm-9">
                    <span sf-form-widget="form.children[key]" class="form-control"/>
                    <div ng-repeat="error in form.children[key].errors" class="help-block">{{ error }}</div>
                </div>
            </div>
        </div>

    </div>
    <div class="modal-footer">
        <a class="btn btn-default" ng-click="dialog.dismiss()">Annuler</a>
        <input class="btn btn-primary" type="submit" value="Enregistrer" />
    </div>
</form>

<div class="modal-header">
    <button type="button" class="close" ng-click="dialog.dismiss()" aria-hidden="true">×</button>
    <h4 class="modal-title"><i class="fa fa-warning"></i> Supprimer un formateur</h4>
</div>
<div class="modal-body center-block">
    <div class="alert alert-danger" role="alert">
        Êtes-vous certain de vouloir supprimer le formateur <strong>{{ dialog.params.trainer.fullName }}</strong> ?
    </div>
</div>
<div class="modal-footer">
    <a class="btn btn-default" ng-click="dialog.dismiss()">Annuler</a>
    <a class="btn btn-primary" ng-click="ok()">Valider</a>
</div>

<a class="typeahead-inscription-item">
    <h4 class="item-name">{{match.label}}</h4>
    <p class="item-complement">{{match.model.organization }}</p>
</a>
<div class="block block-participations">
    <div class="block-title">
        <a class="btn btn-link h4" ui-sref="session.table({trainer: trainer.id})"><span class="fa fa-calendar-o"></span>Sessions</a>
        <div class="pull-right">
            <span class="badge ng-binding">{{ search.result.total ? search.result.total : 0 }}</span>
        </div>
    </div>

    <div class="block-body">
        <div ng-if="!search.result.total" class="well well-empty well-sm">
            {{ emptyMsg }}
        </div>
        <div ng-if="search.result.total">
            <table class="table table-condensed table-nohead">
                <tbody>
                <tr ng-repeat="participation in search.result.items" class="row">
                    <td class="col-xs-3"><span class="text-gray-light text-small">{{ participation.session.dateBegin | date:'dd/MM/yy' }}</span></td>
                    <td class="col-xs-9"><a ui-sref="session.detail.view({id: participation.session.id})">{{ participation.session.training.name }}</a><br>
                        <span class="text-light text-gray-light">{{ participation.session.training.typeLabel }}</span>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <div ng-if="search.result.total > search.query.size" class="full-height-item is-full-width">
            <div search-table-controls></div>
        </div>
    </div>
</div>

<div class="block block-trainer">
    <div class="block-title">
        <div class="full-width">
            <span class="h4"><span class="fa fa-user"></span> {{ trainer.fullName }} </span>
        </div>
    </div>
    <div class="block-body">
        <ul class="summary">
            <li><label>Email</label> {{ trainer.email }}</li>
            <li><label>Téléphone</label> {{ trainer.phoneNumber }}</li>
            <li><label>Formateur interne</label> {{ trainer.isOrganization ? 'Oui' : 'Non' }}</li>
            <li><label>Type</label> {{ trainer.trainerType.name }}</li>
            <li><label>Etablissement</label> {{ trainer.institution.name }}</li>
            <li><label>Nombre d'heures/an</label> {{ trainer.yearHours }}</li>
        </ul>
    </div>
</div>
<div class="row">
    <div class="col-md-8">
        <div class="btn-group pull-right">
            <a class="btn btn-fa" href="" tooltip="Publipostage" ng-click="$dialog.open('batch.publipost', { items: [inscription.id], service: 'inscription' })"><span class="fa fa-file-word-o"></span></a>
            <a class="btn btn-fa" href="" tooltip="Attestation de stage" ng-if="inscription.presenceStatus.status == 1" ng-click="$dialog.open('batch.export.pdf', { items: [inscription.id], service: 'inscription.attestation' })"><span class="fa fa-file-pdf-o"></span></a>
            <a class="btn btn-fa" href="" tooltip="Voir le profil" ng-if="inscription.trainee._accessRights.view" ui-sref="trainee.detail.view({id: inscription.trainee.id})"><span class="fa fa-user"></span></a>
            <a class="btn btn-fa" href="" tooltip="Voir la session" ng-if="inscription.session.training._accessRights.view" ui-sref="session.detail.view({training: inscription.session.training.id, id: inscription.session.id})"><span class="fa fa-calendar-o"></span></a>
            <a class="btn btn-fa" href="" tooltip="Supprimer" ng-if="inscription._accessRights.delete" ng-click="delete()"><span class="fa fa-trash-o"></span></a>
        </div>

        <div class="pre-title">{{ inscription.session.dateBegin|date: 'dd/MM/yyyy' }} - {{ inscription.session.training.name }}</div>

        <h2>Inscription de {{ inscription.trainee.fullName }}</h2>

        <div>
            <strong>Date :</strong> {{ inscription.createdAt | date: 'dd/MM/yyyy HH:mm' }}<br>

            <div class="btn-group dropdown">
                <button class="btn btn-xs dropdown-toggle" ng-class="$root.inscriptionStatusClass(inscription.inscriptionStatus.status, 'btn')" data-toggle="dropdown">{{ inscription.inscriptionStatus.name }} <span class="caret"></span></button>
                <ul class="dropdown-menu text-small">
                    <li ng-repeat="status in inscriptionStatus track by status.id" ng-class="{disabled :inscription.inscriptionStatus.id == status.id}"><a href="" ng-click="updateInscriptionStatus(status)"><i class="fa fa-check" ng-if="inscription.inscriptionStatus.id == status.id"></i> {{ status.name }}</a></li>
                </ul>
            </div>

            <div ng-if="inscription.presenceStatus || inscription.inscriptionStatus.status == 2" class="btn-group dropdown">
                <button class="btn btn-xs dropdown-toggle" ng-class="$root.presenceStatusClass(inscription.presenceStatus.status, 'btn')" data-toggle="dropdown">
                    <span ng-if="inscription.presenceStatus">{{ inscription.presenceStatus.name }}</span>
                    <span ng-if="!inscription.presenceStatus"><em>Statut de présence</em></span>
                    <span class="caret"></span>
                </button>
                <ul class="dropdown-menu text-small">
                    <li ng-repeat="status in presenceStatus track by status.id" ng-class="{disabled :inscription.presenceStatus.id == status.id}"><a href="" ng-click="updatePresenceStatus(status)"><i class="fa fa-check" ng-if="inscription.presenceStatus.id == status.id"></i> {{ status.name }}</a></li>
                </ul>
            </div>
        </div>

        <div class="row mb-1">
            <div class="col-lg-6">
                <h3><i class="fa fa-user"></i> {{ inscription.trainee.title.name }} {{ inscription.trainee.fullName }}</h3><hr>
                <ul class="summary">
                    <li><label>Email</label> {{ inscription.trainee.email }}</li>
                    <li><label>Téléphone</label> {{ inscription.trainee.phoneNumber }}</li>
                    <li><label>Unité</label> {{ inscription.institution.machineName == 'other' ? inscription.otherInstitution : inscription.institution.name }}</li>
                    <li><label>Code postal</label> {{ inscription.trainee.zip }}</li>
                    <li><label>Ville</label> {{ inscription.trainee.city }}</li>
                </ul>
            </div>

            <div class="col-lg-6">
                <h3>Profil professionnel</h3><hr>
                <ul class="summary">
                    <li><label>Catégorie de public</label> {{ inscription.publicType.machineName }}</li>
                    <li><label>Service</label> {{ inscription.trainee.service}}</li>
                    <li><label>Statut</label> {{ inscription.trainee.amuStatut }}</li>
                    <li><label>BAP</label> {{ inscription.trainee.bap }}</li>
                    <li><label>Corps</label> {{ inscription.trainee.corps }}</li>
                    <li><label>Catégorie</label> {{ inscription.trainee.category }}</li>
                    <li><label>Fonction</label> {{ inscription.trainee.fonction }}</li>
                </ul>
            </div>
        </div>

        <form sf-xeditable-form="form" sf-href='inscription.view({id: inscription.id})' on-success="onSuccess(data)">
            <div class="row mb-1">
                <div class="col-lg-12">

                        <h3>Informations relatives à l'inscription</h3>
                        <hr>
                        <ul class="summary">
                            <li><label>Tarif</label> {{ inscription.price ? inscription.price : 0 }} &euro;</li>
                            <li><label>Typologie</label> <span sf-xeditable="form.children.typology">{{ inscription.typology.name }}</span></li>
                            <li><label>Motivation</label> <span sf-xeditable="form.children.motivation" data-type="textarea">{{ inscription.motivation }}</span></li>
                            <li><label>Type d'action de formation</label> <span sf-xeditable="form.children.actiontype" data-type="select">{{ inscription.actiontype.name }}</span></li>
                            <li><label>Compte personnel de formation</label> <span sf-xeditable="form.children.dif">{{inscription.dif ? 'Oui' : 'Non' }}</span> </li>
                            <li><label>Motif de refus (si statut refusé par N+1)</label> {{inscription.refuse }} </li>
                        </ul>


                </div>
            </div>
        </form>


        <div class="row mb-1">
            <div class="col-lg-12">
                <h3>Tableau de présence</h3><hr>
                <!-- Présences -->
                <div ng-include src="'mycompanybundle/inscription/states/detail/partials/presences.html'" ng-controller="PresencesViewController"></div>
            </div>
        </div>

        <div class="row mb-1">
            <div class="col-lg-12">
                <h3>Evaluation de la session</h3><hr>

                <div ng-if="!inscription.criteria.length" class="well well-empty well-sm">
                    Il n'y a pas d'évaluation remplie pour cette session.
                </div>

                <table ng-if="inscription.criteria.length" class="table table-hover table-search table-condensed">
                    <thead>
                        <tr>
                            <th>Critère</th>
                            <th>Evaluation (note de 1 à 5)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr ng-repeat="crit in inscription.criteria">
                            <td> {{ crit.criterion.name }}</td>
                            <td ng-if="crit.note==0"> Non concerné </td>
                            <td ng-if="crit.note!=0"> {{crit.note}} </td>
                        </tr>
                    </tbody>
                </table>
                <hr>
                <ul class="summary">
                    <li><label>Remarques</label> {{ inscription.message }} </li>
                </ul>

            </div>
        </div>

        <div class="alert alert-warning well-sm"><strong>Important :</strong> les informations présentées ci-dessus <strong>sont datées de la date de la session</strong> ({{ inscription.session.dateBegin|date: 'dd/MM/yyyy' }}).
            <span ng-if="inscription.trainee._accessRights.view">Pour obtenir des informations à jour, vous pouvez <a ui-sref="trainee.detail.view({id: inscription.trainee.id})" class="alert-link">consulter le profil du participant <i class="fa fa-external-link"></i></a>.</span>
            <span ng-if="!inscription.trainee._accessRights.view">Pour obtenir des informations à jour, contactez un administrateur de l'{{ inscription.trainee.organization.name }}.</span>
        </div>

    </div>

    <div class="col-sm-4">
        <div resume-session-block="inscription.session"></div>
    </div>
</div>
<a ui-sref="inscription.detail.view({id: result.id})" title="{{ result.session.training.name }} - {{ result.session.dateBegin | date: 'dd/MM/yyyy' }}">
    <div class="list-group-item-title">{{ result.trainee.fullName }}</div>
    <div class="list-group-item-text">
        {{ result.createdAt | date: 'dd/MM/yyyy HH:mm' }}
    </div>
    <div class="list-group-item-text">
        {{ result.session.training.name | characters:80 }} - {{ result.session.dateBegin | date: 'dd/MM/yyyy' }}
    </div>
    <span ng-if="!result.presenceStatus.id" class="label" ng-class="$root.inscriptionStatusClass(result.inscriptionStatus.status)">
        {{ result.inscriptionStatus.name }}
    </span>
    <span ng-if="result.presenceStatus.id" class="label" ng-class="$root.presenceStatusClass(result.presenceStatus.status)">
        {{ result.presenceStatus.name }}
    </span>
</a>

<div class="full-height-container is-full-width is-absolute is-direction-column">

    <!-- Results -->
    <div ng-if="search.result.total" class="full-height-item is-full-width is-grow">
        <div class="col-xs-12">
            <table search-table ng-class="{loading: search.processing}">
                <thead>
                    <tr>
                        <th></th>
                        <th search-table-th field="createdAt">Date</th>
                        <th ng-hide="$stateParams.trainee" search-table-th field="trainee.fullName.source">Stagiaire</th>
                        <th ng-hide="$stateParams.session" search-table-th field="session.dateBegin">Session</th>
                        <th search-table-th field="publicCategory.source">Type de personnel</th>
                        <th search-table-th field="institution.name.source">Établissement</th>
                        <th search-table-th field="sessionPrice.price">Tarif</th>
                        <th search-table-th field="inscriptionStatus.name.source">Inscription</th>
                        <th search-table-th field="presenceStatus.name.source">Présence</th>
                    </tr>
                </thead>
                <tbody>
                    <tr ng-repeat="item in search.result.items" ng-class="{warning: isSelected(item.id)}">
                        <td ng-click="switchSelect(item.id)" stop-event><i class="fa" ng-class="{'fa-square-o': !isSelected(item.id), 'fa-check-square-o': isSelected(item.id)}"></i></td>
                        <td><a href="" ui-sref="inscription.detail.view({ id: item.id })">{{ item.createdAt|date: 'dd/MM/yyyy' }}</a></td>
                        <td ng-hide="$stateParams.trainee"><a href="" ui-sref="trainee.detail.view({ id: item.trainee.id })">{{ item.trainee.fullName }}</a></td>
                        <td ng-hide="$stateParams.session"><a href="" ui-sref="session.detail.view({ id: item.session.id })">{{ item.session.dateBegin|date: 'dd/MM/yyyy' }} - {{ item.session.training.name }}</a></td>
                        <td>{{ item.publicType.name ? item.publicType.name : item.publicType }}</td>
                        <td>{{ item.institution.name }}</td>
                        <td>{{ item.price | number: 2 }} &euro;</td>
                        <td><span class="label label-lg" ng-class="$root.inscriptionStatusClass(item.inscriptionStatus.status)">{{ item.inscriptionStatus.name }}</span></td>
                        <td><span class="label label-lg" ng-class="$root.presenceStatusClass(item.presenceStatus.status)" ng-if="item.presenceStatus">{{ item.presenceStatus.name }}</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div ng-if="search.result.total" class="full-height-item is-full-width">
        <div search-table-controls></div>
    </div>

    <!-- No results -->
    <div class="full-height-item is-full-width is-grow" ng-if="search.executed && !search.result.total">
        <div class="col-xs-12">
            <h1>Oops!</h1>
            <p>Il n'y a aucune inscription correspondante à votre recherche.</p>
        </div>
    </div>

</div>

<div class="modal-header">
    <button type="button" class="close" ng-click="dialog.dismiss()" aria-hidden="true">×</button>
    <h4 class="modal-title"><i class="fa fa-graduation-cap"></i> Editer les présences pour la journée du {{ presence.dateBegin|date: 'dd/MM/yyyy' }}</h4>
</div>

<form sf-href="presence.edit({presence: dialog.params.presence.id})" sf-form="form" json-path="form" on-success="onSuccess(data)" class="form-horizontal" novalidate>
    <div class="modal-body">
        <div class="form-group" ng-class="{'has-error': form.children.morning.errors.length }">
            <label class="col-sm-3 control-label" for="{{ form.children.scheduleMorn.id }}">{{ form.children.morning.label }}</label>
            <div class="col-sm-3" ng-if="!presence.morning">
                <span disabled="true" sf-form-widget="form.children.morning" class="form-control" />
                <div ng-if="error.length" ng-repeat="error in form.children.morning.errors" class="help-block">{{ error }}</div>
            </div>
            <div class="col-sm-3" ng-if="presence.morning">
                <span sf-form-widget="form.children.morning" class="form-control" />
                <div ng-if="error.length" ng-repeat="error in form.children.morning.errors" class="help-block">{{ error }}</div>
            </div>
        </div>
        <div class="form-group" ng-class="{'has-error': form.children.afternoon.errors.length }">
            <label class="col-sm-3 control-label" for="{{ form.children.afternoon.id }}">{{ form.children.afternoon.label }}</label>
            <div class="col-sm-3" ng-if="!presence.afternoon">
                <span disabled="true" sf-form-widget="form.children.afternoon" class="form-control" />
                <div ng-if="error.length" ng-repeat="error in form.children.afternoon.errors" class="help-block">{{ error }}</div>
            </div>
            <div class="col-sm-3" ng-if="presence.afternoon">
                <span sf-form-widget="form.children.afternoon" class="form-control" />
                <div ng-if="error.length" ng-repeat="error in form.children.afternoon.errors" class="help-block">{{ error }}</div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <a class="btn btn-default" ng-click="dialog.dismiss()">Annuler</a>
        <input class="btn btn-primary" type="submit" value="Enregistrer" />
    </div>
</form>



<div class="modal-header">
    <button type="button" class="close" ng-click="cancel()">×</button>
    <h4 class="modal-title" ng-if="inscriptionStatus">Modification du statut d'inscription</h4>
    <h4 class="modal-title" ng-if="presenceStatus">Modification du statut de présence</h4>
</div>
<div class="modal-body">
    <form novalidate class="form-horizontal" role="form">
        <div class="form-group">
            <label class="col-sm-3 control-label">Nombre de stagiaires </label>
            <div class="col-sm-9">
                <span class="form-control">{{ items.length }}</span>
            </div>
        </div>
        <div class="form-group" ng-show="inscriptionStatus">
            <label class="col-sm-3 control-label">Nouveau statut</label>
            <div class="col-sm-9">
                <span class="form-control">{{ inscriptionStatus.name }}</span>
            </div>
        </div>
        <div class="form-group" ng-show="presenceStatus">
            <label class="col-sm-3 control-label">Nouveau statut</label>
            <div class="col-sm-9">
                <span class="form-control">{{ presenceStatus.name }}</span>
            </div>
        </div>
        <div class="form-group">
            <div class="col-sm-3">
            </div>
            <div class="col-sm-9">
                <input type="checkbox" ng-model="send.Mail" id="send_mail"/>
                <label class="control-label" for="send_mail">Envoyer un email</label>
            </div>
        </div>
        <div class="form-group" ng-show='send.Mail' >
            <label for="subject" class="col-sm-3 control-label">Modèle </label>
            <div class="col-sm-9">
                <select type="text" ng-disabled='!send.Mail' class="form-control" id="template" ng-options="template.label for template in templates" ng-model="message.template" placeholder="Modèle du mail">
                </select>
            </div>
        </div>
        <div class="form-group animate-show" ng-show='send.Mail'>
            <label for="subject" class="col-sm-3 control-label">Sujet </label>
            <div class="col-sm-9">
                <input type="text" ng-disabled='!send.Mail' class="form-control" id="subject" ng-model="message.subject" placeholder="Sujet du mail">
            </div>
        </div>
        <div class="form-group" ng-show='send.Mail' >
            <label for="message" class="col-sm-3 control-label">Message </label>
            <div class="col-sm-9">
                <textarea class="form-control" ng-disabled='!send.Mail' rows="10" id="message" ng-model="message.body"  placeholder="Message du mail"></textarea>
            </div>
        </div>
        <div class="form-group" ng-show='send.Mail'>
            <div class="col-sm-3">
            </div>
            <div class="col-sm-9">
            <button type="button" ng-disabled='!send.Mail' class="btn" ng-click="preview()">Prévisualiser</button>
            </div>
        </div>
        <div class="form-group" ng-show='send.Mail && attCheckList.length'>
            <div class="col-sm-3 control-label"><b>Pièces jointes</b> </div>
            <div class="col-sm-5">
                <div ng-repeat="attachmentTemplate in attCheckList">
                <input type="checkbox" ng-model="attachmentTemplate.selected" id="attachment_{{ attachmentTemplate.id }}"/>
                <label class="control-label" for="attachment_{{ attachmentTemplate.id }}">{{ attachmentTemplate.name }}</label><a href="" class="pull-right" ng-click="previewAttachment(attachmentTemplate)"><i class="fa fa-download"></i> Prévisualiser</a>
                </div>
            </div>
        </div>

        <div class="alert alert-danger" ng-show="formError != ''">{{ formError }}</div>
    </form>
</div>
<div class="modal-footer">
    <a class="btn btn-default" ng-click="cancel()">Annuler</a>
    <a class="btn btn-primary" ng-click="ok()">Valider</a>
</div>

<a ui-sref="trainee.detail.view({id: result.id})">
    <label>{{ result.fullName }}</label>
    <p class="list-group-item-text">
        <span ng-if="result.institution"><i class="fa fa-building"></i> {{ result.institution.name }}</span>
    </p>
</a>


<form sf-xeditable-form="form" sf-href='trainee.view({id: trainee.id})' on-success="onSuccess(data)">
    <div class="row">
        <div class="col-md-8">

            <div class="btn-group pull-right">
                <a class="btn btn-fa" href="" tooltip="{{ trainee.isActive ? 'Désactiver le compte' : 'Activer le compte' }}" ng-if="trainee._accessRights.edit" ng-click="toggleActivation()">
                    <span class="fa" ng-class="{'fa-thumbs-o-up': !trainee.isActive, 'fa-thumbs-o-down': trainee.isActive }"></span>
                </a>
                <a class="btn btn-fa" href="" tooltip="Publipostage" ng-click="$dialog.open('batch.publipost', { items: [trainee.id], service: 'trainee' })"><span class="fa fa-file-word-o"></span></a>
                <a class="btn btn-fa" href="" tooltip="Changer le mot de passe" ng-if="trainee._accessRights.edit" ng-click="changePassword()"><span class="fa fa-key"></span></a>
                <a class="btn btn-fa" href="" tooltip="Changer le centre de rattachement" ng-if="$user.hasAccessRight('sygefor_trainee.rights.trainee.all.update')" ng-click="changeOrganization()"><span class="fa fa-university"></span></a>
                <a class="btn btn-fa" href="" tooltip="Supprimer" ng-if="$user.hasAccessRight('sygefor_trainee.rights.trainee.all.update')" ng-click="delete()"><span class="fa fa-trash-o"></span></a>
            </div>

            <div class="pre-title">Inscrit le {{ trainee.createdAt|date: 'dd/MM/yyyy' }} - {{ trainee.organization.name }}</div>

            <h2><span ng-class="{'invalidated': !trainee.isActive }">{{ trainee.fullName }}</span></h2>

            <div class="row">
                <div class="col-lg-6">
                    <h3>Informations personnelles</h3>
                    <hr>
                    <ul class="summary">
                        <li><label>Civilité</label> {{ trainee.title.name }}</li>
                        <li><label>Nom</label> {{ trainee.lastName }}</li>
                        <li><label>Prénom</label> {{ trainee.firstName }}</li>
                        <li><label>Date de naissance</label> {{ trainee.birthDate }}</li>
                        <li><label>Email</label> {{ trainee.email }}</li>
                        <li><label>Téléphone</label> <span sf-xeditable="form.children.phoneNumber">{{ trainee.phoneNumber }}</span></li>
                    </ul>
                </div>

                <div class="col-lg-6">
                    <h3>Adresse</h3>
                    <hr>
                    <ul class="summary">
                        <li><label>Adresse</label> <span sf-xeditable="form.children.address" data-type="textarea">{{ trainee.address }}</span></li>
                        <li><label>Code postal</label> <span sf-xeditable="form.children.zip">{{ trainee.zip }}</span></li>
                        <li><label>Ville</label> <span sf-xeditable="form.children.city">{{ trainee.city }}</span></li>
                    </ul>
                </div>
            </div>

            <div class="row mb-1">
                <div class="col-lg-12">
                    <h3>Informations professionnelles</h3>
                    <hr>
                    <div class="row">
                        <div class="col-lg-6">
                            <ul class="summary">
                                <li><label>Etablissement</label> {{ trainee.institution.name }}</li>
                                <li><label>Campus</label> {{ trainee.campus }}</li>
                                <li><label>Catégorie de public</label> {{ trainee.publicType.machineName }}</li>
                                <li><label>Service</label> {{ trainee.service }}</li>
                                <li><label>Statut</label> {{ trainee.amuStatut }}</li>
                                <li><label>BAP</label> {{ trainee.bap }}</li>
                                <li><label>Corps</label> {{ trainee.corps }}</li>
                                <li><label>Catégorie</label> {{ trainee.category }}</li>
                                <li><label>Fonction exercée</label> <span sf-xeditable="form.children.fonction">{{ trainee.fonction }}</span></li>
                            </ul>
                        </div>
                        <div class="col-lg-6">
                            <ul class="summary">
                                <li><label>Domaine disciplinaire</label> <span sf-xeditable="form.children.disciplinaryDomain" on-change="unset('disciplinary')">{{ trainee.disciplinaryDomain.name }}</span></li>
                                <li ng-show="trainee.disciplinaryDomain"><label>Discipline</label> <span sf-xeditable="form.children.disciplinary">{{ trainee.disciplinary.name }}</span></li>
                                <li><label>Payant</label> <span sf-xeditable="form.children.isPaying">{{ trainee.isPaying ? 'Oui' : 'Non' }}</span></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-1">
                <div class="col-lg-12">
                    <h3>Responsable hiérarchique</h3>
                    <hr>
                    <div class="row">
                        <div class="col-lg-6">
                            <ul class="summary">
                                <li><label>Nom</label> <span sf-xeditable="form.children.lastNameSup">{{ trainee.lastNameSup }}</span></li>
                                <li><label>Prénom</label> <span sf-xeditable="form.children.firstNameSup">{{ trainee.firstNameSup }}</span></li>
                            </ul>
                        </div>
                        <div class="col-lg-6">
                            <ul class="summary">
                                <li><label>Mail</label> <span sf-xeditable="form.children.emailSup">{{ trainee.emailSup }}</span></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-1">
                <div class="col-lg-12">
                    <h3>Autorité hiérarchique</h3>
                    <hr>
                    <div class="row">
                        <div class="col-lg-6">
                            <ul class="summary">
                                <li><label>Nom</label> <span sf-xeditable="form.children.lastNameAut">{{ trainee.lastNameAut }}</span></li>
                                <li><label>Prénom</label> <span sf-xeditable="form.children.firstNameAut">{{ trainee.firstNameAut }}</span></li>
                            </ul>
                        </div>
                        <div class="col-lg-6">
                            <ul class="summary">
                                <li><label>Mail</label> <span sf-xeditable="form.children.emailAut">{{ trainee.emailAut }}</span></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-1">
                <div class="col-lg-12">
                    <h3>Correspondant formation</h3>
                    <hr>
                    <div class="row">
                        <div class="col-lg-6">
                            <ul class="summary">
                                <li><label>Nom</label> <span sf-xeditable="form.children.lastNameCorr">{{ trainee.lastNameCorr }}</span></li>
                                <li><label>Prénom</label> <span sf-xeditable="form.children.firstNameCorr">{{ trainee.firstNameCorr }}</span></li>
                            </ul>
                        </div>
                        <div class="col-lg-6">
                            <ul class="summary">
                                <li><label>Mail</label> <span sf-xeditable="form.children.emailCorr">{{ trainee.emailCorr }}</span></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <ul class="nav nav-tabs">
                <li ng-click="tab = 'inscriptions'" class="active"><a href="" data-toggle="tab"><span class="fa fa-graduation-cap"></span> Inscriptions ({{ trainee.inscriptions.length }})</a></li>
                <li ng-click="tab = 'alerts'" ><a href="" data-toggle="tab"><span class="fa fa-bell"></span> Alertes ({{ trainee.alerts.length }})</a></li>
                <li ng-click="tab = 'messages'"><a href="" data-toggle="tab"><span class="fa fa-send"></span> Messages ({{ trainee.messages.length ? trainee.messages.length : 0 }})</a></li>
            </ul>

            <!--
            INSCRIPTIONS
            -->
            <div ng-show="!tab || tab === 'inscriptions'">
                <div ng-if="!trainee.inscriptions.length" class="well well-empty well-sm">
                    Il n'y a aucune inscription pour ce stagiaire.
                </div>

                <table ng-if="trainee.inscriptions.length" class="table table-hover table-condensed table-responsive table-nohead">
                    <!--<thead>-->
                        <!--<th>Date d'inscription</th>-->
                        <!--<th>Centre</th>-->
                        <!--<th>Fiche de l'inscription</th>-->
                        <!--<th>Fiche de la session</th>-->
                        <!--<th>Statut d'inscription</th>-->
                        <!--<th>Statut de présence</th>-->
                    <!--</thead>-->
                    <tbody>
                    <tr ng-repeat="inscription in trainee.inscriptions | filter:isViewable | orderBy:'createdAt':true">
                        <td>{{ inscription.createdAt | date:'dd/MM/yy' }}</td>
                        <td>{{ inscription.session.training.organization.name }}</td>
                        <td><a ui-sref-access="inscription._accessRights.view" ui-sref="inscription.detail.view({id: inscription.id, session: session.id})">{{ inscription.session.training.name }}</a></td>
                        <td>{{ inscription.session.training.typeLabel }} - Session du <a ui-sref-access="inscription.session._accessRights.view" ui-sref="session.detail.view({id: inscription.session.id})">{{ inscription.session.dateBegin | date:'dd/MM/yyyy' }}</a></td>
                        <td><span class="label" ng-class="$root.presenceStatusClass(inscription.presenceStatus.status)">{{ inscription.presenceStatus.name }}</span></td>
                        <td><span class="label" ng-hide="inscription.presenceStatus" ng-class="$root.inscriptionStatusClass(inscription.inscriptionStatus.status)">{{ inscription.inscriptionStatus.name }}</span></td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <!--
             Alertes
            -->
            <div ng-show="tab === 'alerts'">
                <div class="row mb-1">
                    <div class="col-lg-12">
                        <div>
                            <div ng-if="!trainee.alerts.length" class="well well-empty well-sm">
                                Il n'y a aucune inscription aux alertes pour ce stagiaire.
                            </div>

                            <table ng-if="trainee.alerts.length" class="table table-hover table-search table-condensed">
                                <tbody>
                                <tr ng-repeat="alert in trainee.alerts | filter:filter | orderBy:'createdAt':true">
                                    <td> {{ alert.createdAt | date: 'dd/MM/yyyy' }} </td>
                                    <td> {{ alert.session.training.name }} </td>
                                </tr>
                                </tbody>
                            </table>

                        </div>
                    </div>
                </div>
            </div>

            <!--
             Emails
            -->
            <div ng-show="tab === 'messages'">
                <div class="row mb-1">
                    <div class="col-lg-12">
                        <div entity-emails trainee="trainee.id"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 sidebar">
            <div class="block block-institution" ng-show="trainee.institution">
                <div class="block-title">
                    <div class="btn-group full-width">
                        <a ui-sref="institution.detail.view({id: trainee.institution.id})" class="btn btn-link h4"><span class="fa fa-institution"></span>Unité : {{ trainee.institution.name }}</a>
                    </div>
                </div>

                <div class="block-body text-gray-light text-light">
                    <div class="row mb-1">
                        <div class="col-xs-12">
                            <span>{{ trainee.institution.address }} {{ trainee.institution.zip }} {{ trainee.institution.city }}</span>
                        </div>
                    </div>

                    <div class="row mb-1">
                        <div class="col-xs-12">
                            <div><strong><span class="fa fa-user"></span> Directeur</strong></div>
                            <span>{{ trainee.institution.manager.fullName }}</span><br>
                            <span ng-if="trainee.institution.manager.email"><a href="mailto:{{ trainee.institution.manager.email }}" target="_blank">{{ trainee.institution.manager.email }}</a><br></span>
                            <span ng-if="trainee.institution.manager.phoneNumber">{{ trainee.institution.manager.phoneNumber }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
<div class="full-height-container is-full-width is-absolute is-direction-column">

    <!-- Results -->
    <div ng-if="search.result.total" class="full-height-item is-full-width is-grow">
        <div class="col-xs-12">
            <table search-table ng-class="{loading: search.processing}">
                <thead>
                    <tr>
                        <th></th>
                        <th search-table-th field="organization.name.source">Centre</th>
                        <th search-table-th field="title">Civilite</th>
                        <th search-table-th field="lastName.source">Nom</th>
                        <th search-table-th field="createdAt">Inscription</th>
                        <th search-table-th field="institution.name.source">Etablissement</th>
                        <th search-table-th field="publicType.source">Catégorie de personnel</th>
                    </tr>
                </thead>
                <tbody>
                    <tr ng-repeat="item in search.result.items" ng-class="{danger: !item.isActive, warning: isSelected(item.id)}">
                        <td ng-click="switchSelect(item.id)" stop-event><i class="fa" ng-class="{'fa-square-o': !isSelected(item.id), 'fa-check-square-o': isSelected(item.id)}"></i></td>
                        <td>{{ item.organization.name }}</td>
                        <td>{{ item.title.name ? item.title.name : item.title }}</td>
                        <td><a href="" ui-sref="trainee.detail.view({ id: item.id })">{{ item.fullName }}</a></td>
                        <td>{{ item.createdAt | date: 'dd/MM/yyyy' }}</td>
                        <td>{{ item.institution.name }}</td>
                        <td>{{ item.publicType.name ? item.publicType.name : item.publicType }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div ng-if="search.result.total" class="full-height-item is-full-width">
        <div search-table-controls></div>
    </div>

    <!-- No results -->
    <div ng-if="search.executed && !search.result.total" class="full-height-item is-full-width is-grow">
        <div class="col-xs-12">
            <h1>Oops!</h1>
            <p>Il n'y a aucune personne correspondante à votre recherche.</p>
        </div>
    </div>

</div>

<div class="full-height-container is-full-width is-absolute is-direction-column">

    <!-- Results -->
    <div ng-if="search.result.total" class="full-height-item is-full-width is-grow">
        <div class="col-xs-12">
            <table search-table ng-class="{loading: search.processing}">
                <thead>
                    <tr>
                        <th></th>
                        <th search-table-th field="name.source">Intitulé</th>
                        <th search-table-th field="zip">Code postal</th>
                        <th search-table-th field="city.source">Ville</th>
                    </tr>
                </thead>
                <tbody>
                    <tr ng-repeat="item in search.result.items" ng-class="{warning: isSelected(item.id)}">
                        <td ng-click="switchSelect(item.id)" stop-event><i class="fa" ng-class="{'fa-square-o': !isSelected(item.id), 'fa-check-square-o': isSelected(item.id)}"></i></td>
                        <td><a href="" ui-sref="institution.detail.view({ id: item.id })">{{ item.name }}</a></td>
                        <td>{{ item.zip }}</td>
                        <td>{{ item.city }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div ng-if="search.result.total" class="full-height-item is-full-width">
        <div search-table-controls></div>
    </div>

    <!-- No results -->
    <div ng-if="search.executed && !search.result.total" class="full-height-item is-full-width is-grow">
        <div class="col-xs-12">
            <h1>Oops!</h1>
            <p>Il n'y a aucune unité correspondante à votre recherche.</p>
        </div>
    </div>

</div>

<form sf-xeditable-form="form" sf-href='institution.view({id: institution.id})' on-success="onSuccess(data)">
    <div class="row">
        <div class="col-md-8">

            <div class="btn-group pull-right">
                <a class="btn btn-fa" href="" tooltip="Changer le centre de rattachement" ng-if="$user.hasAccessRight('sygefor_institution.rights.institution.all.update')" ng-click="changeOrganization()"><span class="fa fa-university"></span></a>
                <a class="btn btn-fa" href="" tooltip="Supprimer" ng-if="institution._accessRights.delete" ng-click="delete()"><span class="fa fa-trash-o"></span></a>
            </div>

            <div class="pre-title">Créé le {{ institution.createdAt|date: 'dd/MM/yyyy' }} - {{ institution.organization.name }}</div>
            <h2><span sf-xeditable="form.children.name">{{ institution.name }}</span></h2>

            <div class="row">
                <div class="col-lg-6">
                    <h3>Informations</h3>
                    <hr>
                    <ul class="summary">
                        <li><label>Adresse</label> <span sf-xeditable="form.children.address" data-type="textarea">{{ institution.address }}</span></li>
                        <li><label>Code postal</label> <span sf-xeditable="form.children.zip">{{ institution.zip }}</span></li>
                        <li><label>Ville</label> <span sf-xeditable="form.children.city">{{ institution.city }}</span></li>
                    </ul>
                </div>
                <div class="col-lg-6">
                    <h3>Directeur de l'établissement</h3>
                    <hr>
                    <ul class="summary">
                        <li><label>Nom</label> <span sf-xeditable="form.children.manager.children.lastName">{{ institution.manager.lastName }}</span></li>
                        <li><label>Prénom</label> <span sf-xeditable="form.children.manager.children.firstName">{{ institution.manager.firstName }}</span></li>
                        <li><label>Téléphone</label> <span sf-xeditable="form.children.manager.children.phoneNumber">{{ institution.manager.phoneNumber }}</span></li>
                        <li><label>Email</label> <span sf-xeditable="form.children.manager.children.email">{{ institution.manager.email }}</span></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</form>

<a ui-sref="institution.detail.view({id: result.id})">
    <label>{{ result.name }}</label>
    <p class="list-group-item-text">
        {{ result.institutionType.name ? result.institutionType.name : result.institutionType  }}
    </p>
</a>
<div class="full-height-container is-full-width is-absolute is-direction-column">

    <!-- Results -->
    <div ng-if="search.result.total" class="full-height-item is-full-width is-grow">
        <div class="col-xs-12">
            <table search-table ng-class="{loading: search.processing}">
                <thead>
                    <tr>
                        <th></th>
                        <th search-table-th field="organization.name.source">Centre</th>
                        <th search-table-th field="lastName.source">Nom</th>
                        <th search-table-th field="institution.name.source">Etablissement</th>
                        <th search-table-th field="service.source">Service</th>
                        <th search-table-th field="isOrganization">Statut</th>
                        <th search-table-th field="isArchived">Archivé</th>
                        <th search-table-th field="isPublic">Publié</th>
                    </tr>
                </thead>
                <tbody>
                    <tr ng-repeat="item in search.result.items" ng-class="{warning: isSelected(item.id)}">
                        <td ng-click="switchSelect(item.id)" stop-event><i class="fa" ng-class="{'fa-square-o': !isSelected(item.id), 'fa-check-square-o': isSelected(item.id)}"></i></td>
                        <td>{{ item.organization.name }}</td>
                        <td><a href="" ui-sref="trainer.detail.view({ id: item.id })">{{ item.fullName }}</a></td>
                        <td>{{ item.institution.name }}</td>
                        <td>{{ item.service }}</td>
                        <td>{{ item.isOrganization ? 'Formateur interne' : 'Formateur externe' }}</td>
                        <td><span ng-class="{'label label-lg label-danger': item.isArchived}">{{ item.isArchived === true ? 'Oui' : 'Non' }}</span></td>
                        <td><span class="label label-lg" ng-class="{'label-success': item.isPublic, 'label-danger': !item.isPublic}">{{ item.isPublic ? 'Publié' : 'Non publié' }}</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div ng-if="search.result.total" class="full-height-item is-full-width">
        <div search-table-controls></div>
    </div>

    <!-- No results -->
    <div class="full-height-item is-full-width is-grow" ng-if="search.executed && !search.result.total">
        <div class="col-xs-12">
            <h1>Oops!</h1>
            <p>Il n'y a aucun formateur correspondant à votre recherche.</p>
        </div>
    </div>

</div>

<a ui-sref="trainer.detail.view({id: result.id})">
    <label>{{ result.fullName }}</label>
    <p class="list-group-item-text">
        <span><i class="fa fa-building"></i> {{ result.institution.name }}</span>
    </p>
</a>


<form sf-xeditable-form="form" sf-href='trainer.view({id: trainer.id})' on-success="onSuccess(data)">
    <div class="row">
        <div class="col-md-8">
            <div class="btn-group pull-right">
                <a class="btn btn-fa" href="" tooltip="Changer le centre de rattachement" ng-click="changeOrganization()"><span class="fa fa-university"></span></a>
                   <!--ng-if="$user.hasAccessRight('sygefor_trainer.rights.trainer.all.update')" -->
                <a class="btn btn-fa" href="" tooltip="Publipostage" ng-click="$dialog.open('batch.publipost', { items: [trainer.id], service: 'trainer' })"><span class="fa fa-file-word-o"></span></a>
                <a class="btn btn-fa" href="" tooltip="Supprimer" ng-if="trainer._accessRights.delete" ng-click="delete()"><span class="fa fa-trash-o"></span></a>
            </div>

            <div class="pre-title">Enregistré le {{ trainer.createdAt|date: 'dd/MM/yyyy' }} - {{ trainer.organization.name }}</div>

            <h2>{{ trainer.fullName }}</h2>
            <h3><span>Informations personnelles</span></h3>
            <hr>
            <div class="row mb-1">
                <div class="col-lg-6">
                    <ul class="summary">
                        <li><label>Nom</label> <span sf-xeditable="form.children.lastName">{{ trainer.lastName }}</span></li>
                        <li><label>Prénom</label> <span sf-xeditable="form.children.firstName">{{ trainer.firstName }}</span></li>
                        <li><label>Email</label> <span sf-xeditable="form.children.email">{{ trainer.email }}</span></li>
                        <li><label>Téléphone</label> <span sf-xeditable="form.children.phoneNumber">{{ trainer.phoneNumber }}</span></li>
                        <li><label>Site web</label> <span sf-xeditable="form.children.website">{{ trainer.website }}</span></li>
                        <li><label>Publié sur le web</label>  <span sf-xeditable="form.children.isPublic">{{ trainer.isPublic ? 'Oui' : 'Non' }}</span></li>
                        <li title="Autoriser l'envoie de courriels pour les stagiaires"><label>Autoriser les courriels</label> <span sf-xeditable="form.children.isAllowSendMail">{{ trainer.isAllowSendMail === true || trainer.isAllowSendMail === undefined ? 'Oui' : 'Non' }}</span></li>
                    </ul>
                </div>
                <div class="col-lg-6">
                    <ul class="summary">
                        <li><label>Etablissement</label> <span sf-xeditable="form.children.institution">{{ trainer.institution.name }}</span></li>
                        <li><label>Formateur interne</label> <span sf-xeditable="form.children.isOrganization">{{ trainer.isOrganization ? 'Oui' : 'Non' }}</span></li>
                        <li><label>Service</label> <span sf-xeditable="form.children.service">{{ trainer.service }}</span></li>
                        <li><label>Fonction, statut</label>  <span sf-xeditable="form.children.status">{{ trainer.status }}</span></li>
                        <li><label>Archivé</label> <span sf-xeditable="form.children.isArchived">{{ trainer.isArchived ? 'Oui' : 'Non' }}</span></li>
                    </ul>
                </div>
            </div>

            <h3>Coordonnées</h3>
            <hr>
            <div class="row mb-1">
                <div class="col-lg-6">
                    <ul class="summary">
                        <li><label>Adresse</label> <span sf-xeditable="form.children.address" data-type="textarea">{{ trainer.address }}</span></li>
                        <li><label>Code postal</label> <span sf-xeditable="form.children.zip">{{ trainer.zip }}</span></li>
                        <li><label>Ville</label> <span sf-xeditable="form.children.city">{{ trainer.city }}</span></li>
                    </ul>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <h3><span>Observations</span></h3><hr>
                    <div class="row">
                        <div class="col-lg-10">
                            <p><span sf-xeditable="form.children.observations" data-type="textarea">{{ trainer.observations }}</span></p>

                        </div>
                    </div>

                    <!-- Emails -->
                    <div entity-emails trainer="trainer.id"></div>
                </div>
            </div>
        </div>

        <div class="col-md-4 sidebar">
            <!--
            PARTICIPATIONS
            -->
            <div participations-block="trainer"></div>
        </div>
    </div>
</form>

<div class="block block-materials">
    <div class="block-title">
        <div class="full-width">
            <span class="h4"><span class="fa fa-paperclip"></span> Supports</span>
            <div class="pull-right">
                <a tooltip="Gérer les supports" ng-click="manageMaterials()" href="" class="btn btn-fa btn-sm"><span class="fa fa-folder-open-o"></span></a>
                <span class="badge">{{ entity.materials.length }}</span>
            </div>
        </div>
    </div>
    <div class="block-body">
        <div class="well well-empty well-sm" ng-if="!entity.materials.length">
            {{ emptyMsg }}
        </div>
        <ul class="list-unstyled">
            <li ng-repeat="material in entity.materials">
                <a href="" ng-click="getMaterial(material)" ng-if="!material.url">{{ material.fileName }}</a>
                <a href="{{material.url}}" target="_blank" ng-if="material.url">{{ material.name }}</a>
            </li>
        </ul>
    </div>
</div>

<a class="typeahead-inscription-item">
    <h4 class="item-name">{{match.label}}</h4>
    <p class="item-complement">{{match.model.organization }}</p>
</a>
<div class="block block-dates">
    <div class="block-body">
        <div class="mb-1">
            <div class="pull-left">
                <a class="btn btn-xs btn-default" href="" ng-click="addDates()"><span class="fa fa-plus"></span> Ajouter une date</a>
            </div>
        </div>

        <br><br>

        <div ng-if="!session.dates.length" class="well well-empty well-sm">
            {{ emptyMsg }}
        </div>

        <div ng-if="session.dates.length">
            <table class="table table-hover table-search table-condensed">
                <thead>
                <tr>
                    <th>Date début</th>
                    <th>Date fin</th>
                    <th>Horaires matin</th>
                    <th>Horaires après-midi</th>
                    <th>Nombres d'heures</th>
                    <th>Lieu</th>
                    <th>Modifier</th>
                    <th>Supprimer</th>
                </tr>
                </thead>
                <tbody>
                <tr ng-repeat="date in session.dates | orderBy:'dateBegin':false" >
                    <td>{{ date.dateBegin | date: 'dd/MM/yyyy' }}</td>
                    <td>{{ date.dateEnd | date: 'dd/MM/yyyy' }}</td>
                    <td>{{ date.scheduleMorn }}</td>
                    <td>{{ date.scheduleAfter }}</td>
                    <td>{{ date.hourNumber }}</td>
                    <td>{{ date.place }}</td>
                    <td><a class="btn btn-fa" href="" ng-click="editDates(date)" tooltip="Modifier"><span class="fa fa-pencil"></span></a></td>
                    <td><a class="btn btn-fa" href="" ng-click="removeDates(date)" tooltip="Supprimer"><span class="fa fa-trash-o"></span></a></td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="block block-inscriptions">
    <div class="block-title">
        <div class="full-width">

            <!-- title -->
            <a ui-sref="inscription.table({session: session.id})" class="btn btn-link h4"><span class="fa fa-graduation-cap"></span>Inscriptions</a>

            <!-- stat -->
            <span ng-if="session.registration == 3 && session.registrable" class="label label-success">ouvertes</span>
            <span ng-if="session.registration == 2 && session.registrable" class="label label-warning">privées</span>
            <span ng-if="!session.registrable" class="label label-danger">closes</span>

            <!-- filter -->
            <span>&nbsp;
                <span class="btn-group">
                    <a href="" class="btn btn-link dropdown-toggle" data-toggle="dropdown">
                        <span class="caret"></span>
                        <span class="text-small text-gray-light">{{ filterLabel || 'Tous les statuts (' + session.inscriptions.length + ')' }}</span>
                    </a>
                    <ul class="dropdown-menu" role="menu">
                        <!--li class="dropdown-header">Filtrer par statut</li-->
                        <li><a href="" ng-click="resetFilter()"><i class="fa fa-check" ng-if="!filter.inscriptionStatus && !filter.presenceStatus"></i> Tous les statuts ({{ session.inscriptions.length }})</a></li>
                        <li class="divider"></li>
                        <li ng-repeat="status in status.inscription track by status.id" ng-class="{disabled :status.count == 0}"><a href="" ng-click="filterByStatus('inscription', status)"><i class="fa fa-check" ng-if="filter.inscriptionStatus.id == status.id"></i> {{ status.name }} ({{ status.count }})</a></li>
                        <li class="divider"></li>
                        <li ng-repeat="status in status.presence track by status.id" ng-class="{disabled :status.count == 0}"><a href="" ng-click="filterByStatus('presence', status)"><i class="fa fa-check" ng-if="filter.presenceStatus.id == status.id"></i> {{ status.name }} ({{ status.count }})</a></li>
                    </ul>
                </span>
            </span>


            <!-- operation -->
            <div class="pull-right">
                <a ng-if="session._accessRights.edit" class="btn btn-fa btn-sm" href="" tooltip="Ajouter une inscription" tooltip-placement="left" ng-click="addInscription()"><span class="fa fa-plus"></span></a><!--
                --><span class="badge" ng-class="inscriptionBadgeClassName()" tooltip="{{ totalAcceptedInscriptions() }} acceptés sur {{ session.maximumNumberOfRegistrations }} places" tooltip-placement="left">{{ totalAcceptedInscriptions() }} / {{ session.maximumNumberOfRegistrations }}</span>
            </div>
        </div>
    </div>


    <div class="block-body">
        <div ng-if="!session.inscriptions.length" class="well well-empty well-sm">
            {{ emptyMsg }}
        </div>
        <div ng-if="session.inscriptions.length">
            <table class="table table-condensed table-nohead">
                <tbody>
                <tr ng-repeat="inscription in session.inscriptions | filter:filter | orderBy:'createdAt':true">
                    <td><span class="text-gray-light text-small">{{ inscription.createdAt | date:'dd/MM/yy' }}</span></td>
                    <td>
                        <a dialog-href-deprecated="inscription.detail({id: inscription.id})" ui-sref="inscription.detail.view({id: inscription.id, session: session.id})">{{ inscription.trainee.fullName }}</a>
		    </td>
                    <td class="text-right">
		            <div class="btn-group dropdown">
                		<button class="btn btn-xs dropdown-toggle" ng-class="$root.inscriptionStatusClass(inscription.inscriptionStatus.status, 'btn')" data-toggle="dropdown">{{ inscription.inscriptionStatus.name }} <span class="caret"></span></button>
                		<ul class="dropdown-menu text-small">
                    			<li ng-repeat="status in inscriptionStatus track by status.id" ng-class="{disabled :inscription.inscriptionStatus.id == status.id}"><a href="" ng-click="updateInscriptionStatus(status)"><i class="fa fa-check" ng-if="inscription.inscriptionStatus.id == status.id"></i> {{ status.name }}</a></li>
                		</ul>
            		   </div>

		            <div ng-if="inscription.presenceStatus || inscription.inscriptionStatus.status == 2" class="btn-group dropdown">
                		<button class="btn btn-xs dropdown-toggle" ng-class="$root.presenceStatusClass(inscription.presenceStatus.status, 'btn')" data-toggle="dropdown">
                    			<span ng-if="inscription.presenceStatus">{{ inscription.presenceStatus.name }}</span>
                    			<span ng-if="!inscription.presenceStatus"><em>Statut de présence</em></span>
                    			<span class="caret"></span>
                		</button>
                		<ul class="dropdown-menu text-small">
                    			<li ng-repeat="status in presenceStatus track by status.id" ng-class="{disabled :inscription.presenceStatus.id == status.id}"><a href="" ng-click="updatePresenceStatus(status)"><i class="fa fa-check" ng-if="inscription.presenceStatus.id == status.id"></i> {{ status.name }}</a></li>
                		</ul>
            		   </div>

                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        <div class="block" ng-if="session.displayOnline === true && session.registration !== 2">
            <div class="block-body">
                <div input-copy-clipboard="session.publicUrl"></div>
            </div>
        </div>

        <div class="block" ng-if="session.registration === 2">
            <div class="block-body">
                <div input-copy-clipboard="session.privateUrl"></div>
            </div>
        </div>
    </div>
</div>

<div class="block block-inscriptions">
    <div class="block-title">
        <div class="full-width">
            <!-- title -->
            <span class="h4"><span class="fa fa-graduation-cap"></span> Participants</span>
            <!-- badge -->
            <span class="badge pull-right">{{ getTotal() }}</span>
        </div>
    </div>

    <div class="block-body">
        <ul class="list-unstyled text-gray">
            <li ng-repeat="public in publics">
                <label class="text-small">{{ public.name }}</label>
                <span class="pull-right inline-block" sf-xeditable="getFormElement(public)" data-mode="popup" data-placement="left">{{ getCount(public) }}</span>
            </li>
        </ul>
    </div>
</div>

<span ng-if="session.registration">
    <span ng-if="!session.registration" class="label label-danger" ng-class="class">désactivées</span>
    <span ng-if="session.registration == 3 && session.registrable" class="label label-success" ng-class="class" tooltip="Inscriptions ouvertes">ouvertes</span>
    <span ng-if="session.registration == 2 && session.registrable" class="label label-warning" ng-class="class" tooltip="Inscriptions privées">privées</span>
    <span ng-if="session.registration == 1 && !session.registrable" class="label label-danger" ng-class="class" tooltip="Inscriptions fermées">fermées</span>
    <span ng-if="session.registration > 1 && !session.registrable">
        <span ng-if="$moment().isAfter(session.limitRegistrationDate)" class="label label-danger" ng-class="class" tooltip="Date limite d'inscription atteinte : {{ session.limitRegistrationDate|date: 'dd/MM/yyyy' }}">terminées</span>
        <span ng-if="!$moment().isAfter(session.limitRegistrationDate) && session.numberOfAcceptedRegistrations >= session.maximumNumberOfRegistrations" class="label label-danger" ng-class="class" tooltip="Nombre max de participants atteint : {{ session.maximumNumberOfRegistrations }}">complet</span>
    </span>
</span>

<div class="block block-session">
    <div class="block-title">
        <div class="full-width">
            <span class="h4"><span class="fa fa-calendar"></span> {{ session.training.name }} - {{ session.name }}</span>
        </div>
    </div>
    <div class="block-body">
        <ul class="summary">
            <li><label>Thématique</label> {{ session.training.theme.name }} </li>
            <li><label>Date(s)</label> {{ session.dateRange }}</li>
            <li><label>Durée</label> {{ session.hourNumber  + ' heure(s) sur ' + session.dayNumber + ' jour(s)' }}</li>
            <li><label>Lieu</label> {{ session.place.name }}</li>
        </ul>
    </div>
</div>
<div class="block block-trainers">
    <div class="block-title">
        <div class="full-width">
            <a ui-sref="trainer.table({session: session.id})" class="btn btn-link h4"><span class="fa fa-user"></span>Formateurs</a>
            <div class="pull-right">
                <a class="btn btn-fa btn-sm" href="" tooltip="Ajouter un formateur" tooltip-placement="left" ng-click="addTrainer()"><span class="fa fa-plus"></span></a><!--
                --><span class="badge">{{ session.participations.length }}</span>
            </div>
        </div>
    </div>

    <div class="block-body">
        <div class="well well-empty well-sm" ng-if="!session.participations.length">
            {{ emptyMsg }}
        </div>
        <div ng-if="session.participations.length">
            <span ng-repeat="participation in session.participations">
                <button type="button" class="close close-inline" ng-click="removeTrainer(participation)"><span aria-hidden="true">&times;</span></button>
                <a href="" ui-sref="trainer.detail.view({session: participation.session.id, id: participation.trainer.id})"> <!-- ng-click="editParticipation(participation)" -->{{ participation.trainer.fullName }}</a>
                {{$last ? '' : ', '}}
            </span>
            <br>
            <br>
            <a class="btn btn-xs btn-default" href="" ng-click="sendConvo()"><span class="fa fa-envelope"></span> Envoyer les convocations</a>
        </div>
    </div>
</div>

<div ng-if="items.length">
    <table class="table table-hover table-search table-condensed">
        <thead>
        <tr>
            <th>Date</th>
            <th>Type</th>
            <th>Titre</th>
            <th>Inscriptions</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
            <tr ng-repeat="item in items">
                <td><a href="" ui-sref="session.detail.view({ id: item.id, training: item.training.id })" ui-sref-widget-opts>{{ item.dateBegin|date: 'dd/MM/yyyy' }}</a></td>
                <td>{{ item.training.typeLabel }}</td>
                <td><span class="fa fa-star fa-highlight" ng-if="item.promote" title="Session promue"></span> <a href="" ui-sref="training.detail.view({ id: item.training.id })">{{ item.training.name }}</a></td>
                <td>
                    <span ng-repeat="stat in item.inscriptionStats |filter:{status:'!2'}| orderBy:'count':1">
                        <a href="" ui-sref="inscription.table({session: item.id, status: stat.id})" class="label label-default" tooltip="{{ stat.name }} : {{ stat.count }}" tooltip-placement="bottom" ng-class="$root.inscriptionStatusClass(stat.status)">{{ stat.count }}</a>
                    </span>
                </td>
                <td>
                    <a registration-stats-label="item"></a>
                </td>
            </tr>
        </tbody>
    </table>
    <pagination ng-if="search.result.total > search.query.size" total-items="search.result.total" items-per-page="search.query.size" ng-model="search.query.page" class="pagination-sm" max-size="5" previous-text="&lsaquo;" next-text="&rsaquo;"></pagination>
</div>

<div class="widget-empty-msg" ng-if="!items.length && !loading">{{ options.emptymsg || 'Aucune session dans cette liste.' }}</div>
