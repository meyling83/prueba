/**
     * Adiciona los estudiantes
     * 
     * @param Request $request
     * @param EstudianteRepository $estudianteRepository
     * @return Response
     * 
     * @Route("/new", name="estudiante_new", methods={"GET", "POST"})
     */
    public function new(Request $request, EstudianteRepository $estudianteRepository): Response
    {
        /**
         * Asegurarse que el usuario esta autenticado
         */
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        /**
         * @var \Doctrine\Persistence\ObjectManager $em
         */
        $em = $this->getDoctrine()->getManager();
        
        $estudiante = new Estudiante();
        $form    = $this->createForm(EstudianteType::class, $estudiante, array('allow_extra_fields' => true))
        ->add('fechaNacimiento', TextType::class, [
            'label'    => false,
            'mapped'   => false,
            'required' => true,
            'attr'     => array(
                'class'                      => 'form-control is-valid',
                'placeholder'                => 'Introduzca la fecha de nacimiento',
                'data-inputmask-alias'       => 'datetime',
                'data-inputmask-inputformat' => 'dd-mm-yyyy',
                'data-inputmask-placeholder' => 'dd-mm-yyyy',
                'title'                      => 'Introduzca la fecha de nacimiento',
            )
            ]);
        
        $form->handleRequest($request);
      
       
        /**
             * Necsito aqui coger los checkbox que selecciono el usuario, para adicionar esas asignaturas al estudiante. Es lo que no se hacer
             */

        if ($form->isSubmitted() && $form->isValid()) {
            $allDatas = $request->request->all();
            
            /**
             * Validaciones
             */
            if (!$this->validateDatasForm($allDatas, $estudiante->getCi(), true)) { return $this->redirectToRoute('estudiante_new'); }

            $municipio      = $em->getRepository(Municipio::class)->findOneBy(array('nombre'=>$allDatas['form']['municipio']));

              /**
             * Cuando tengo un campo de fecha, para usar el tex con la mascara para la fecha y que no me de error porque se pasa
             * un string a la fecha, tengo que poner otro nombre al campo de fecha en el formType, tambien cambio el nombre en el from del template y aqui 
             * asigno de sta forma la fecha 
             */          
            $estudiante->setFechaNacimiento($this->getDateFormat($allDatas['estudiante']['fechaNacimiento']));
            if (isset($request->request->get('estudiante')['graduado'])) { if ($request->request->get('estudiante')['graduado']=='on') { $estudiante->setGraduado(1); } }
            if (isset($request->request->get('estudiante')['casoSocial'])) { if ($request->request->get('estudiante')['casoSocial']=='on') { $estudiante->setCasoSocial(1); } }
            if (isset($request->request->get('estudiante')['altoRendimiento'])) { if ($request->request->get('estudiante')['altoRendimiento']=='on') { $estudiante->setAltoRendimiento(1); } }
           
            $em->persist($estudiante);

           
            $em->flush();
            
            $this->addFlash('notice', $this->getMsg('successSave'));
            
            $this->insertOperationSystem( $this->getMsg('titleOperacionAddEstudiante'), $this->getUser(), $em );
            
            if ($allDatas['estudiante']['operation'] == 'aplicar') { return $this->redirectToRoute('estudiante_new'); }
            
            return $this->redirectToRoute('estudiante_index', [], Response::HTTP_SEE_OTHER);
        }
        $asignaturas    = $em->getRepository(Asignatura::class)->findAll();
        $dataSpecific = array(
            'titlecurrentpage'   => $this->getMsg('titlePageAddEstudiante'),
            'titleagrupacionmod' => $this->getMsg('titleModuloNomencladores'),
            'titlemodulo'        => $this->getMsg('titleSubModuloEstudiante'),
            'asignaturas'        =>$asignaturas,
            'menunomencladores'  => true,
            'menuaestudiantes'   => true,
            'actionNew'          => true,
            'asignaturas'               => $asignaturasChecked,
            'form'               => $form->createView(),
        );
        
        
        return $this->render('estudiante/new.html.twig', array_merge($this->getGeneralDatas(), $dataSpecific));
    }
