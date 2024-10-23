<?php

namespace App\Controller\Controller;

use App\Entity\Image;
use App\Entity\User;
use App\Enum\FlashEnum;
use App\Form\Filter\UserFilterType;
use App\Form\Form\UserServiceFormType;
use App\Repository\ReviewRepository;
use App\Repository\UserRepository;
use App\Service\ImageUploader\Contract\Base64ImageUploaderInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class UserController extends AbstractController
{

    public function __construct(
        private readonly UserRepository               $userRepository,
        private readonly ReviewRepository             $reviewRepository,
        private readonly PaginatorInterface           $paginator,
        private readonly Base64ImageUploaderInterface $base64ImageUploader
    )
    {
    }

    #[Route(path: '/', name: 'services')]
    public function index(Request $request): Response
    {
        $userFilterForm = $this->createForm(UserFilterType::class);
        $userFilterForm->handleRequest($request);

        $usersPagination = $this->paginator->paginate(target: [], page: $request->query->getInt('page', 1), limit: 30);

        if ($userFilterForm->isSubmitted() && $userFilterForm->isValid()) {
            $usersQuery = $this->userRepository->findByUserFilterDto(userFilterDto: $userFilterForm->getData(), isQuery: true);
            $usersPagination = $this->paginator->paginate(target: $usersQuery, page: $request->query->getInt('page', 1), limit: 30);

            return $this->render('app/index.html.twig', [
                'userFilterForm' => $userFilterForm,
                'usersPagination' => $usersPagination
            ]);
        }

        return $this->render('app/index.html.twig', [
            'userFilterForm' => $userFilterForm,
            'usersPagination' => $usersPagination
        ]);
    }

    #[Route(path: '/dashboard', name: 'user_dashboard')]
    public function dashboard(#[CurrentUser] User $currentUser, Request $request): Response
    {
        $serviceProfileForm = $this->createForm(UserServiceFormType::class, $currentUser);

        $serviceProfileForm->handleRequest($request);
        if ($serviceProfileForm->isSubmitted() && $serviceProfileForm->isValid()) {
            $avatar = $serviceProfileForm->get('avatar')->getData();
            $servicePhotos = $serviceProfileForm->get('servicePhotos')->getData();
            $skills = $serviceProfileForm->get('servicePhotos')->getData();

            if ($avatar instanceof UploadedFile) {
                $avatarBase64 = $this->base64ImageUploader->process(realPath: $avatar->getRealPath(), width: 300, height: 300);
                $currentUser->setAvatar(avatar: $avatarBase64);
            }

            /**
             * @var UploadedFile $servicePhoto
             */
            foreach ($servicePhotos as $servicePhoto) {
                $avatarBase64 = $this->base64ImageUploader->process(realPath: $servicePhoto->getRealPath());
                $image = new Image(owner: $currentUser, base64: $avatarBase64, oldFilename: $servicePhoto->getClientOriginalName());
                $currentUser->addImage(image: $image);
            }

            $this->userRepository->save(entity: $currentUser, flush: true);
            $this->addFlash(type: FlashEnum::SUCCESS->value, message: 'changes saved');
            return $this->redirectToRoute(route: 'user_dashboard');
        }

        return $this->render('user/dashboard.html.twig', [
            'serviceProfileForm' => $serviceProfileForm
        ]);
    }

    #[Route(path: '/show/{id}', name: 'show_service')]
    public function show(User $user, Request $request): Response
    {
        $reviewsQuery = $this->reviewRepository->findByReviewee(userId: $user->getId(), isQuery: true);
        $reviewsPagination = $this->paginator->paginate(target: $reviewsQuery, page: $request->query->getInt('page', 1), limit: 5);

        return $this->render('user/show.html.twig', [
            'user' => $user,
            'reviewsPagination' => $reviewsPagination
        ]);
    }

}