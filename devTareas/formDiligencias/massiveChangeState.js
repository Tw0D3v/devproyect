
// eslint-disable-next-line no-var
var _MassiveChangeState = "controladores/funcionesMassiveChangeState.php";
// eslint-disable-next-line no-var
var MassiveChangeState = class MassiveChangeState extends Formmulario3{
    constructor(){
        super();
        const frm = this;
    }

    assignChange(){
        const frm = this;
        frm._("#EestadoActual").change(function(){
            frm.listardiligencia();
        })
        frm._("#EestadoActual").change(function(){
            frm.listardiligencia();
        })
        frm.post("../controladores/funcionesMassiveChangeState.php",{functionphp: "obtenerEstado",oidDiligencia: this.value }, function(data){
            frm.listardiligencias();
        })
    }

    assignClicks(){
        const frm = this;
        const datosForm = Formmulario3.tomarCamposForm(frm._$("#frmDatos"));
        frm._$("#btn-grabar").clicks(function(){
            frm.post("../controladores/funcionesMassiveChangeState.php",{functionphp:"grabarDatos",datosForm:datosForm},function (data){
                frm.listarDiliencias(accion,oidMsg)
            })
        })
    }

    instantiateTables(){
        const frm = this;
        frm.tblMassiveChange = new simpleDatatable.DataTable("#tblMassiveChange" + frm.oidOption,{
            searchable: false,
            paging: true,
            perPageSelect: false,
            perPage: 1000,
            layout: {
                top: "{info}",
                bottom: ""
            },
            labels: {
                placeholder: "Buscando registros...",
                noRows: "No hay registros con estos parámetros",
                info: "Mostrando {rows} registros"
            },
            columns: [{
                select: [0, 1],
                sortable: false
            }, {
                select: [1],
                createdCell: function (celda) {
                    new DOMElement(celda, frm).addClass("tdcmd");
                }
            }]
        });

        frm.tblMassiveChange.on("dataTable.update", function () {
            frm._$("#tblMassiveChange").element.querySelectorAll("tbody tr").forEach(fila => {
                const tdAction = fila.cells[0];
                tdAction.prop("title", "click para elegir esta categoria").click(function () {
                    const idMgs = this.querySelectorAll("span").id;
                    const tituloDig= this.querySelectorAll("span").innerHTML;
                    const aDatos = {
                        titulo: tituloDig,
                        oid: oidMsg
                    };
                    frm.listardiligencia(aDatos);
                });
            });
        });

    }


    listarDiliencias (oidMsg,accion) {
        const frm = this;
        frm._$("#tblMassiveChange");
        frm.tblMassiveChange.clear();
        frm.tblMassiveChange.data = [];
        frm.post("../controladores/funcionesMassiveChangeState.php",{functionphp:"listarDiligencias",oid:frm.oidMsg,accion:accion}, function(data){
            if(data.lstDiligencia){
                frm.tblMassiveChange.insert(data.lstDiligencia);
            }
        });
    }

}